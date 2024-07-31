<?php

/*
 * Copyright (c) 2024 AIPTU
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/AIPTU/MyNotes
 */

declare(strict_types=1);

namespace aiptu\mynotes;

use DateTimeImmutable;
use InvalidArgumentException;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\DisablePluginException;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pad;
use function array_values;
use function count;
use function is_array;
use function is_string;
use function mb_strimwidth;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function strtolower;
use function trim;

class MyNotes extends PluginBase {
	private DataConnector $db;

	private array $titles = [];
	private array $messages = [];
	private array $buttons = [];
	private array $icons = [];

	private const DATE_TIME_FORMAT = 'Y-m-d H:i:s';

	public function onEnable() : void {
		try {
			$this->loadConfig();
		} catch (\Throwable $e) {
			$this->getLogger()->error('An error occurred while loading the configuration: ' . $e->getMessage());
			throw new DisablePluginException();
		}

		$this->db = libasynql::create($this, $this->getConfig()->get('database'), [
			'sqlite' => 'sqlite.sql',
			'mysql' => 'mysql.sql',
		]);

		$this->db->executeGeneric('notes.create');
		$this->db->waitAll();
	}

	public function onDisable() : void {
		$this->db->close();
	}

	/**
	 * Loads and validates the plugin configuration from the config.yml file.
	 * If the configuration is invalid, an exception will be thrown.
	 *
	 * @throws InvalidArgumentException when the configuration is invalid
	 */
	private function loadConfig() : void {
		$config = $this->getConfig();

		$this->titles = $this->loadConfigSection($config, 'titles', 'title_');
		$this->messages = $this->loadConfigSection($config, 'messages', 'message_');
		$this->buttons = $this->loadConfigSection($config, 'buttons', 'button_');
		$this->icons = $this->loadConfigSection($config, 'icons', 'icon_');
	}

	private function loadConfigSection(Config $config, string $section, string $prefix) : array {
		$values = $config->get($section, []);
		if (!is_array($values)) {
			throw new InvalidArgumentException("Invalid {$section} settings. '{$section}' must be an array.");
		}

		$requiredKeys = array_filter(ConfigKeys::cases(), fn ($key) => str_starts_with($key->value, $prefix));

		foreach ($requiredKeys as $key) {
			$keyWithoutPrefix = str_replace($prefix, '', $key->value);
			if (!isset($values[$keyWithoutPrefix]) || !is_string($values[$keyWithoutPrefix])) {
				throw new InvalidArgumentException("Missing or invalid {$section} value for '{$keyWithoutPrefix}'.");
			}
		}

		return $values;
	}

	private function getTitle(ConfigKeys $key) : string {
		$keyWithoutPrefix = str_replace('title_', '', strtolower($key->name));
		return $this->titles[$keyWithoutPrefix] ?? 'Default Title';
	}

	private function getMessage(ConfigKeys $key) : string {
		$keyWithoutPrefix = str_replace('message_', '', strtolower($key->name));
		return $this->messages[$keyWithoutPrefix] ?? 'Default Message';
	}

	private function getButton(ConfigKeys $key) : string {
		$keyWithoutPrefix = str_replace('button_', '', strtolower($key->name));
		return $this->buttons[$keyWithoutPrefix] ?? 'Default Button';
	}

	private function getIcon(ConfigKeys $key) : string {
		$keyWithoutPrefix = str_replace('icon_', '', strtolower($key->name));
		return $this->icons[$keyWithoutPrefix] ?? 'Default Icon';
	}

	private function getPlayerNotes(Player $player, callable $callback) : void {
		$this->db->executeSelect('notes.select_all', [
			'player_uuid' => $player->getUniqueId()->toString(),
		], function (array $rows) use ($callback) : void {
			$notes = $this->formatNotes($rows);
			$callback($notes);
		}, function (SqlError $error) : void {
			$this->getLogger()->error('Failed to fetch player notes: ' . $error->getErrorMessage());
		});
	}

	private function saveNoteForPlayer(Player $player, string $title, array $note) : void {
		$this->db->executeInsert('notes.insert', [
			'player_uuid' => $player->getUniqueId()->toString(),
			'note_title' => $title,
			'note_content' => $note['content'],
			'created_at' => $note['created'],
			'modified_at' => $note['modified'],
			'pinned' => $note['pinned'] ? 1 : 0,
		], null, function (SqlError $error) : void {
			$this->getLogger()->error('Failed to save player note: ' . $error->getErrorMessage());
		});
	}

	private function deleteNoteForPlayer(Player $player, string $noteTitle) : void {
		$this->db->executeChange('notes.delete', [
			'player_uuid' => $player->getUniqueId()->toString(),
			'note_title' => $noteTitle,
		], null, function (SqlError $error) : void {
			$this->getLogger()->error('Failed to delete player note: ' . $error->getErrorMessage());
		});
	}

	private function getNoteForPlayer(Player $player, string $noteTitle, callable $callback) : void {
		$this->db->executeSelect('notes.select_single', [
			'player_uuid' => $player->getUniqueId()->toString(),
			'note_title' => $noteTitle,
		], function (array $rows) use ($callback) : void {
			if (count($rows) === 0) {
				$callback(null);
				return;
			}

			$note = $this->formatNote($rows[0]);
			$callback($note);
		}, function (SqlError $error) : void {
			$this->getLogger()->error('Failed to fetch single player note: ' . $error->getErrorMessage());
		});
	}

	private function formatNotes(array $notes) : array {
		$result = [];
		foreach ($notes as $note) {
			$result[$note['note_title']] = [
				'content' => $note['note_content'],
				'created' => $note['created_at'],
				'modified' => $note['modified_at'],
				'pinned' => $note['pinned'],
			];
		}

		return $result;
	}

	private function formatNote(array $note) : array {
		return [
			'content' => $note['note_content'],
			'created' => $note['created_at'],
			'modified' => $note['modified_at'],
			'pinned' => $note['pinned'],
		];
	}

	private function displaySimpleForm(Player $player, string $title, string $content, array $buttons, callable $onSubmit) : void {
		$form = new SimpleForm($onSubmit);
		$form->setTitle($title);
		$form->setContent($content);

		foreach ($buttons as $button) {
			$form->addButton($button['text'], 0, $button['imagePath']);
		}

		$player->sendForm($form);
	}

	private function displayCustomForm(Player $player, string $title, array $inputs, callable $onSubmit) : void {
		$form = new CustomForm($onSubmit);
		$form->setTitle($title);

		foreach ($inputs as $input) {
			$type = $input['type'] ?? 'input';
			$label = $input['label'];
			$placeholder = $input['placeholder'] ?? '';
			$default = $input['default'] ?? '';

			switch ($type) {
				case 'input':
					$form->addInput($label, $placeholder, $default);
					break;
				case 'toggle':
					$form->addToggle($label, (bool) $default);
					break;
			}
		}

		$player->sendForm($form);
	}

	private function displayModalForm(Player $player, string $title, string $content, string $button1, string $button2, callable $onSubmit) : void {
		$form = new ModalForm($onSubmit);
		$form->setTitle($title);
		$form->setContent($content);
		$form->setButton1($button1);
		$form->setButton2($button2);

		$player->sendForm($form);
	}

	public function openMainMenu(Player $player) : void {
		$this->getPlayerNotes($player, function (array $notes) use ($player) : void {
			$notesCount = count($notes);

			$buttons = [
				['text' => $this->getButton(ConfigKeys::BUTTON_CREATE_NOTE), 'imagePath' => $this->getIcon(ConfigKeys::ICON_CREATE)],
			];

			if ($notesCount > 0) {
				$buttons = array_merge($buttons, [
					['text' => $this->getButton(ConfigKeys::BUTTON_DELETE_NOTE), 'imagePath' => $this->getIcon(ConfigKeys::ICON_DELETE)],
					['text' => $this->getButton(ConfigKeys::BUTTON_VIEW_NOTES), 'imagePath' => $this->getIcon(ConfigKeys::ICON_VIEW)],
					['text' => $this->getButton(ConfigKeys::BUTTON_EDIT_NOTE), 'imagePath' => $this->getIcon(ConfigKeys::ICON_EDIT)],
					['text' => $this->getButton(ConfigKeys::BUTTON_SHARE_NOTE), 'imagePath' => $this->getIcon(ConfigKeys::ICON_SHARE)],
				]);
			}

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_MAIN_MENU),
				$notesCount > 0
					? $this->getMessage(ConfigKeys::MESSAGE_SELECT_ACTION) . "\n\n§aYou have §e{$notesCount} §anotes."
					: $this->getMessage(ConfigKeys::MESSAGE_NO_NOTES),
				$buttons,
				function (Player $player, ?int $data = null) : void {
					match ($data) {
						0 => $this->openNewNoteForm($player),
						1 => $this->openDeleteNoteForm($player),
						2 => $this->openViewNotesForm($player),
						3 => $this->openEditNoteForm($player),
						4 => $this->openShareNoteForm($player),
						default => null,
					};
				}
			);
		});
	}

	public function openNewNoteForm(Player $player) : void {
		$this->displayCustomForm(
			$player,
			$this->getTitle(ConfigKeys::TITLE_CREATE_NOTE),
			[
				['label' => 'Title:', 'placeholder' => 'Enter note title', 'default' => ''],
				['label' => 'Content:', 'placeholder' => 'Enter note content', 'default' => ''],
				['label' => 'Pin this note?', 'type' => 'toggle', 'default' => false],
			],
			function (Player $player, ?array $data = null) : void {
				if ($data === null) {
					return;
				}

				[$title, $content, $pinned] = array_pad($data, 3, '');
				$title = trim($title);

				if ($title === '') {
					$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NOTE_TITLE_EMPTY));
					$this->openNewNoteForm($player);
					return;
				}

				$this->getNoteForPlayer($player, $title, function (?array $existingNote) use ($player, $title, $content, $pinned) : void {
					if ($existingNote !== null) {
						$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_TITLE_EXISTS), $title));
						$this->openNewNoteForm($player);
						return;
					}

					$content = str_replace('{line}', "\n", $content);

					$noteData = [
						'content' => $content,
						'created' => (new DateTimeImmutable())->format(self::DATE_TIME_FORMAT),
						'modified' => (new DateTimeImmutable())->format(self::DATE_TIME_FORMAT),
						'pinned' => $pinned,
					];

					$this->saveNoteForPlayer($player, $title, $noteData);
					$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_CREATED), $title));

					if ($pinned) {
						$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_PINNED), $title));
					}

					$this->openMainMenu($player);
				});
			}
		);
	}

	private function createNoteButtons(array $notes) : array {
		$createButton = fn ($title, $content, $isPinned) => [
			'text' => ($isPinned ? ' §6' : '§6') . TextFormat::colorize("{$title}\n§7" . mb_strimwidth(TextFormat::clean($content), 0, 30) . '...'),
			'imagePath' => $this->getIcon(ConfigKeys::ICON_VIEW),
		];

		$pinnedNotes = array_filter($notes, fn ($note) => $note['pinned'] ?? false);
		$unpinnedNotes = array_filter($notes, fn ($note) => !($note['pinned'] ?? false));

		$buttons = array_merge(
			array_map(
				fn ($title, $note) => $createButton($title, $note['content'], true),
				array_keys($pinnedNotes),
				$pinnedNotes
			),
			array_map(
				fn ($title, $note) => $createButton($title, $note['content'], false),
				array_keys($unpinnedNotes),
				$unpinnedNotes
			),
			[['text' => $this->getButton(ConfigKeys::BUTTON_BACK), 'imagePath' => $this->getIcon(ConfigKeys::ICON_BACK)]]
		);

		$allNotes = array_merge(array_keys($pinnedNotes), array_keys($unpinnedNotes));

		return [$buttons, $allNotes];
	}

	public function openViewNotesForm(Player $player) : void {
		$this->getPlayerNotes($player, function (array $notes) use ($player) : void {
			[$buttons, $allNotes] = $this->createNoteButtons($notes);

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_VIEW_NOTES),
				$this->getMessage(ConfigKeys::MESSAGE_SELECT_NOTE),
				$buttons,
				function (Player $player, ?int $data = null) use ($allNotes) : void {
					if ($data === null || !isset($allNotes[$data])) {
						$this->openMainMenu($player);
						return;
					}

					$noteTitle = $allNotes[$data];
					$this->openNoteContentForm($player, (string) $noteTitle);
				}
			);
		});
	}

	public function openNoteContentForm(Player $player, string $noteTitle) : void {
		$this->getNoteForPlayer($player, $noteTitle, function (?array $note) use ($player, $noteTitle) : void {
			if ($note === null) {
				$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NOTE_NOT_FOUND));
				return;
			}

			$content = $note['content'] ?? '';
			$created = $note['created'] ?? 'Unknown';
			$modified = $note['modified'] ?? 'Unknown';
			$pinned = $note['pinned'] ? 'Yes' : 'No';

			$message = sprintf(
				"§eTitle: §6%s\n§eCreated: §6%s\n§eModified: §6%s\n§ePinned: §6%s\n\n§6%s",
				TextFormat::colorize($noteTitle),
				$created,
				$modified,
				$pinned,
				TextFormat::colorize($content)
			);

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_NOTE_CONTENT),
				$message,
				[['text' => $this->getButton(ConfigKeys::BUTTON_BACK), 'imagePath' => $this->getIcon(ConfigKeys::ICON_BACK)]],
				function (Player $player, ?int $data = null) : void {
					$this->openMainMenu($player);
				}
			);
		});
	}

	public function openDeleteNoteForm(Player $player) : void {
		$this->getPlayerNotes($player, function (array $notes) use ($player) : void {
			[$buttons, $allNotes] = $this->createNoteButtons($notes);

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_DELETE_NOTE),
				$this->getMessage(ConfigKeys::MESSAGE_SELECT_NOTE_DELETE),
				$buttons,
				function (Player $player, ?int $data = null) use ($allNotes) : void {
					if ($data === null || !isset($allNotes[$data])) {
						$this->openMainMenu($player);
						return;
					}

					$noteTitle = $allNotes[$data];
					$this->confirmDeleteNoteForm($player, (string) $noteTitle);
				}
			);
		});
	}

	public function confirmDeleteNoteForm(Player $player, string $noteTitle) : void {
		$this->displayModalForm(
			$player,
			$this->getTitle(ConfigKeys::TITLE_CONFIRM_DELETE_NOTE),
			sprintf($this->getMessage(ConfigKeys::MESSAGE_CONFIRM_DELETE_NOTE), TextFormat::colorize($noteTitle)),
			$this->getButton(ConfigKeys::BUTTON_TEXT_YES),
			$this->getButton(ConfigKeys::BUTTON_TEXT_NO),
			function (Player $player, ?bool $data = null) use ($noteTitle) : void {
				if ($data === null) {
					$this->openMainMenu($player);
					return;
				}

				if ($data) {
					$this->deleteNoteForPlayer($player, $noteTitle);
					$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_DELETED), $noteTitle));
				}

				$this->openMainMenu($player);
			}
		);
	}

	public function openEditNoteForm(Player $player) : void {
		$this->getPlayerNotes($player, function (array $notes) use ($player) : void {
			[$buttons, $allNotes] = $this->createNoteButtons($notes);

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_EDIT_NOTE),
				$this->getMessage(ConfigKeys::MESSAGE_SELECT_NOTE_EDIT),
				$buttons,
				function (Player $player, ?int $data = null) use ($allNotes) : void {
					if ($data === null || !isset($allNotes[$data])) {
						$this->openMainMenu($player);
						return;
					}

					$noteTitle = $allNotes[$data];
					$this->openEditNoteDetailForm($player, (string) $noteTitle);
				}
			);
		});
	}

	public function openEditNoteDetailForm(Player $player, string $noteTitle) : void {
		$this->getNoteForPlayer($player, $noteTitle, function (?array $note) use ($player, $noteTitle) : void {
			if ($note === null) {
				$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NOTE_NOT_FOUND));
				return;
			}

			$notePinned = (bool) $note['pinned'];

			$this->displayCustomForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_EDIT_NOTE),
				[
					['label' => 'Title:', 'placeholder' => 'Enter note title', 'default' => $noteTitle],
					['label' => 'Content:', 'placeholder' => 'Enter note content', 'default' => $note['content']],
					['label' => 'Pin this note?', 'type' => 'toggle', 'default' => $notePinned],
				],
				function (Player $player, ?array $data = null) use ($noteTitle, $note, $notePinned) : void {
					if ($data === null) {
						return;
					}

					[$newTitle, $newContent, $pinned] = array_pad($data, 3, '');
					$newTitle = trim($newTitle);
					$pinned = (bool) $pinned;

					if ($newTitle === '') {
						$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NOTE_TITLE_EMPTY));
						$this->openEditNoteDetailForm($player, $noteTitle);
						return;
					}

					$this->getNoteForPlayer($player, $newTitle, function (?array $existingNote) use ($player, $noteTitle, $newTitle, $newContent, $pinned, $note, $notePinned) : void {
						if ($newTitle !== $noteTitle && $existingNote !== null) {
							$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_TITLE_EXISTS), $newTitle));
							$this->openEditNoteDetailForm($player, $noteTitle);
							return;
						}

						$newContent = str_replace('{line}', "\n", $newContent);

						$contentChanged = $newContent !== $note['content'];
						$titleChanged = $newTitle !== $noteTitle;
						$pinChanged = $pinned !== $notePinned;

						if (!$contentChanged && !$titleChanged && !$pinChanged) {
							$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NO_CHANGES));
							$this->openMainMenu($player);
							return;
						}

						$noteData = array_merge($note, [
							'content' => $newContent,
							'modified' => (new DateTimeImmutable())->format(self::DATE_TIME_FORMAT),
							'pinned' => $pinned,
						]);

						if ($titleChanged) {
							$this->deleteNoteForPlayer($player, $noteTitle);
							$this->saveNoteForPlayer($player, $newTitle, $noteData);
							$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_RENAMED), $noteTitle, $newTitle));
						} else {
							$this->saveNoteForPlayer($player, $noteTitle, $noteData);
							$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_UPDATED), $noteTitle));
						}

						if ($pinChanged) {
							$pinMessage = $pinned
								? sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_PINNED), $newTitle)
								: sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_UNPINNED), $newTitle);
							$player->sendMessage($pinMessage);
						}

						$this->openMainMenu($player);
					});
				}
			);
		});
	}

	public function openShareNoteForm(Player $player) : void {
		$this->getPlayerNotes($player, function (array $notes) use ($player) : void {
			[$buttons, $allNotes] = $this->createNoteButtons($notes);

			$this->displaySimpleForm(
				$player,
				$this->getTitle(ConfigKeys::TITLE_SHARE_NOTE),
				$this->getMessage(ConfigKeys::MESSAGE_SELECT_NOTE_SHARE),
				$buttons,
				function (Player $player, ?int $data = null) use ($allNotes) : void {
					if ($data === null || !isset($allNotes[$data])) {
						$this->openMainMenu($player);
						return;
					}

					$noteTitle = $allNotes[$data];
					$this->openShareNotePlayerForm($player, (string) $noteTitle);
				}
			);
		});
	}

	public function openShareNotePlayerForm(Player $player, string $noteTitle) : void {
		$onlinePlayers = array_filter($this->getServer()->getOnlinePlayers(), fn ($p) => $p->getName() !== $player->getName());
		$playerNames = array_values(array_map(fn ($p) => $p->getName(), $onlinePlayers));

		if (count($playerNames) === 0) {
			$player->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NO_PLAYERS_ONLINE));
			$this->openMainMenu($player);
			return;
		}

		$buttons = array_map(fn ($name) => ['text' => "§d{$name}", 'imagePath' => $this->getIcon(ConfigKeys::ICON_PLAYER)], $playerNames);
		$buttons[] = ['text' => $this->getButton(ConfigKeys::BUTTON_BACK), 'imagePath' => $this->getIcon(ConfigKeys::ICON_BACK)];

		$this->displaySimpleForm(
			$player,
			$this->getTitle(ConfigKeys::TITLE_SELECT_PLAYER) . ' - ' . $noteTitle,
			$this->getMessage(ConfigKeys::MESSAGE_SELECT_PLAYER),
			$buttons,
			function (Player $player, ?int $data = null) use ($noteTitle, $playerNames) : void {
				if ($data === null || !isset($playerNames[$data])) {
					$this->openMainMenu($player);
					return;
				}

				$recipientName = $playerNames[$data];
				$recipient = $this->getServer()->getPlayerExact($recipientName);

				if ($recipient === null) {
					$player->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_PLAYER_NOT_FOUND), $recipientName));
					$this->openMainMenu($player);
					return;
				}

				$this->displayModalForm(
					$player,
					$this->getTitle(ConfigKeys::TITLE_CONFIRM_SHARE_NOTE),
					sprintf($this->getMessage(ConfigKeys::MESSAGE_CONFIRM_SHARE_NOTE), TextFormat::colorize($noteTitle), $recipientName),
					$this->getButton(ConfigKeys::BUTTON_TEXT_YES),
					$this->getButton(ConfigKeys::BUTTON_TEXT_NO),
					function (Player $player, ?bool $data = null) use ($noteTitle, $recipient) : void {
						if ($data === null) {
							$this->openMainMenu($player);
							return;
						}

						if ($data) {
							$this->shareNoteWithPlayer($player, $recipient, $noteTitle);
						}

						$this->openMainMenu($player);
					}
				);
			}
		);
	}

	public function shareNoteWithPlayer(Player $sender, Player $recipient, string $noteTitle) : void {
		$this->getNoteForPlayer($sender, $noteTitle, function (?array $note) use ($sender, $recipient, $noteTitle) : void {
			if ($note === null) {
				$sender->sendMessage($this->getMessage(ConfigKeys::MESSAGE_NOTE_NOT_FOUND));
				return;
			}

			$this->saveNoteForPlayer($recipient, $noteTitle, $note);

			$sender->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_SHARED), $noteTitle, $recipient->getName()));
			$recipient->sendMessage(sprintf($this->getMessage(ConfigKeys::MESSAGE_NOTE_RECEIVED), $noteTitle, $sender->getName()));
		});
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
		if (!$sender instanceof Player) {
			$sender->sendMessage($this->getMessage(ConfigKeys::MESSAGE_COMMAND_ONLY_INGAME));
			return false;
		}

		$this->openMainMenu($sender);
		return true;
	}
}

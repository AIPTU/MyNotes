<?php

/*
 * Copyright (c) 2024 HazardTeam
 *
 * For the full copyright and license information, please view
 * the LICENSE.md file that was distributed with this source code.
 *
 * @see https://github.com/HazardTeam/Lottery
 */

declare(strict_types=1);

namespace aiptu\mynotes;

enum ConfigKeys : string {
	case TITLE_MAIN_MENU = 'title_main_menu';
	case TITLE_CREATE_NOTE = 'title_create_note';
	case TITLE_VIEW_NOTES = 'title_view_notes';
	case TITLE_DELETE_NOTE = 'title_delete_note';
	case TITLE_CONFIRM_DELETE_NOTE = 'title_confirm_delete_note';
	case TITLE_EDIT_NOTE = 'title_edit_note';
	case TITLE_NOTE_CONTENT = 'title_note_content';
	case TITLE_SHARE_NOTE = 'title_share_note';
	case TITLE_CONFIRM_SHARE_NOTE = 'title_confirm_share_note';
	case TITLE_SELECT_PLAYER = 'title_select_player';

	case MESSAGE_NO_NOTES = 'message_no_notes';
	case MESSAGE_SELECT_ACTION = 'message_select_action';
	case MESSAGE_SELECT_NOTE = 'message_select_note';
	case MESSAGE_SELECT_NOTE_DELETE = 'message_select_note_delete';
	case MESSAGE_SELECT_NOTE_EDIT = 'message_select_note_edit';
	case MESSAGE_SELECT_NOTE_SHARE = 'message_select_note_share';
	case MESSAGE_SELECT_PLAYER = 'message_select_player';
	case MESSAGE_NOTE_CREATED = 'message_note_created';
	case MESSAGE_NOTE_UPDATED = 'message_note_updated';
	case MESSAGE_NOTE_DELETED = 'message_note_deleted';
	case MESSAGE_NOTE_RENAMED = 'message_note_renamed';
	case MESSAGE_NOTE_SHARED = 'message_note_shared';
	case MESSAGE_NOTE_RECEIVED = 'message_note_received';
	case MESSAGE_NO_CHANGES = 'message_no_changes';
	case MESSAGE_NOTE_PINNED = 'message_note_pinned';
	case MESSAGE_NOTE_UNPINNED = 'message_note_unpinned';
	case MESSAGE_NOTE_NOT_FOUND = 'message_note_not_found';
	case MESSAGE_PLAYER_NOT_FOUND = 'message_player_not_found';
	case MESSAGE_NO_PLAYERS_ONLINE = 'message_no_players_online';
	case MESSAGE_COMMAND_ONLY_INGAME = 'message_command_only_ingame';
	case MESSAGE_CONFIRM_DELETE_NOTE = 'message_confirm_delete_note';
	case MESSAGE_CONFIRM_SHARE_NOTE = 'message_confirm_share_note';
	case MESSAGE_NOTE_TITLE_EMPTY = 'message_note_title_empty';
	case MESSAGE_NOTE_TITLE_EXISTS = 'message_note_title_exists';

	case BUTTON_CREATE_NOTE = 'button_create_note';
	case BUTTON_DELETE_NOTE = 'button_delete_note';
	case BUTTON_VIEW_NOTES = 'button_view_notes';
	case BUTTON_EDIT_NOTE = 'button_edit_note';
	case BUTTON_SHARE_NOTE = 'button_share_note';
	case BUTTON_BACK = 'button_back';
	case BUTTON_TEXT_YES = 'button_text_yes';
	case BUTTON_TEXT_NO = 'button_text_no';

	case ICON_CREATE = 'icon_create';
	case ICON_DELETE = 'icon_delete';
	case ICON_VIEW = 'icon_view';
	case ICON_EDIT = 'icon_edit';
	case ICON_SHARE = 'icon_share';
	case ICON_BACK = 'icon_back';
	case ICON_PLAYER = 'icon_player';
}

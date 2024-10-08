# MyNotes Plugin

A Pocketmine-MP plugin that allows players to create, manage, and share personal notes in-game.

## Features

- **Create Notes**: Players can create new notes with a title and content.
- **Edit Notes**: Players can edit the content of existing notes.
- **Delete Notes**: Players can delete their notes.
- **Share Notes**: Players can share their notes with other players.
- **Database Integration**: Notes are stored in a database, ensuring persistence across server restarts.
- **User Interface**: Interactive forms for creating, viewing, editing, and deleting notes.

## Commands

- `/notes`: Main command for managing notes.

## Permissions

- `mynotes.command`: Allows the player to use the `/notes` command.
  - Default: `true`

## Default Config
```yaml
# Database settings
database:
  # The database type. "sqlite" and "mysql" are supported.
  type: sqlite

  # Edit these settings only if you choose "sqlite".
  sqlite:
    # The file name of the database in the plugin data folder.
    # You can also put an absolute path here.
    file: data.sqlite
  # Edit these settings only if you choose "mysql".
  mysql:
    host: 127.0.0.1
    # Avoid using the "root" user for security reasons.
    username: root
    password: ""
    schema: your_schema
  # The maximum number of simultaneous SQL queries
  # Recommended: 1 for sqlite, 2 for MySQL. You may want to further increase this value if your MySQL connection is very slow.
  worker-limit: 1

# Titles used in various parts of the MyNotes plugin
titles:
  main_menu: "§l§bMyNotes"  # Title for the main menu
  create_note: "§l§aCreate New Note"  # Title for creating a new note
  view_notes: "§l§bYour Notes"  # Title for viewing your notes
  delete_note: "§l§cDelete Note"  # Title for deleting a note
  confirm_delete_note: "§l§cConfirm Deletion"  # Title for confirming the deletion of a note
  edit_note: "§l§eEdit Note"  # Title for editing a note
  note_content: "§l§aNote Content"  # Title for viewing note content
  share_note: "§l§dShare a Note"  # Title for sharing a note
  confirm_share_note: "§l§cConfirm Share"  # Title for confirming the sharing of a note
  select_player: "§l§dSelect Player"  # Title for selecting a player to share a note with

# Messages displayed to users during various interactions
messages:
  no_notes: "§eYou don't have any notes yet."  # Message when the user has no notes
  select_action: "§eChoose what you want to do:"  # Prompt to select an action
  select_note: "§eSelect a note to view:"  # Prompt to select a note to view
  select_note_delete: "§eSelect a note to delete:"  # Prompt to select a note to delete
  select_note_edit: "§eSelect a note to edit:"  # Prompt to select a note to edit
  select_note_share: "§eSelect a note to share:"  # Prompt to select a note to share
  select_player: "§eSelect a player to share with:"  # Prompt to select a player to share with
  note_created: "§aNote '%s' has been created successfully."  # Confirmation when a note is created
  note_updated: "§eNote '%s' has been updated successfully."  # Confirmation when a note is updated
  note_deleted: "§cNote '%s' has been deleted."  # Confirmation when a note is deleted
  note_renamed: "§eNote '%s' has been renamed to '%s' and updated successfully."  # Confirmation when a note is renamed and updated
  note_shared: "§aNote '%s' has been shared with %s."  # Confirmation when a note is shared
  note_received: "§aYou have received a note '%s' from %s."  # Notification when a note is received
  no_changes: "§eNo changes were made to the note."  # Message when no changes are made to a note
  note_pinned: "§aNote '%s' has been pinned."  # Confirmation when a note is pinned
  note_unpinned: "§aNote '%s' has been unpinned."  # Confirmation when a note is unpinned
  note_not_found: "§cNote not found or data is invalid."  # Error when a note is not found
  player_not_found: "§cPlayer %s not found."  # Error when a player is not found
  no_players_online: "§cThere are no other players online to share your note with."  # Message when no players are online to share with
  command_only_ingame: "§cThis command can only be used in-game."  # Error when a command is used outside of the game
  confirm_delete_note: "§eAre you sure you want to delete the note: §c%s?"  # Confirmation prompt for deleting a note
  confirm_share_note: "§eAre you sure you want to share the note: §c%s §eto §6%s?"  # Confirmation prompt for sharing a note
  note_title_empty: "§cNote title cannot be empty."  # Error when a note title is empty
  note_title_exists: "§cA note with the title '%s' already exists."  # Error when a note title already exists

# Button labels for various actions
buttons:
  create_note: "§2Create Note"  # Button text for creating a note
  delete_note: "§cDelete Note"  # Button text for deleting a note
  view_notes: "§bView Notes"  # Button text for viewing notes
  edit_note: "§eEdit Note"  # Button text for editing a note
  share_note: "§dShare Note"  # Button text for sharing a note
  back: "§cBack"  # Button text for going back
  text_yes: "§aYes"  # Button text for confirming an action
  text_no: "§cNo"  # Button text for denying an action

# Icons used for various actions (only paths from Minecraft are supported)
icons:
  create: "textures/ui/recipe_book_icon"  # Icon for creating a note
  delete: "textures/ui/book_trash_default"  # Icon for deleting a note
  view: "textures/items/paper"  # Icon for viewing notes
  edit: "textures/items/book_enchanted"  # Icon for editing a note
  share: "textures/ui/share_google"  # Icon for sharing a note
  back: "textures/ui/arrowLeft"  # Icon for going back
  player: "textures/ui/icon_steve"  # Icon for a player

```

## Upcoming Features

- Currently none planned. You can contribute or suggest for new features.

## Additional Notes

- If you find bugs or want to give suggestions, please visit [here](https://github.com/AIPTU/MyNotes/issues).
- We accept all contributions! If you want to contribute, please make a pull request in [here](https://github.com/AIPTU/MyNotes/pulls).
- Icons made from [www.flaticon.com](https://www.flaticon.com)

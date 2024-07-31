-- #!sqlite

-- #{ notes

-- #    { create
CREATE TABLE IF NOT EXISTS notes (
    player_uuid TEXT NOT NULL,
    note_title TEXT NOT NULL,
    note_content TEXT,
    created_at TEXT,
    modified_at TEXT,
    pinned INTEGER,
    PRIMARY KEY (player_uuid, note_title)
);
-- #    }

-- #    { insert
-- #      :player_uuid string
-- #      :note_title string
-- #      :note_content string
-- #      :created_at string
-- #      :modified_at string
-- #      :pinned int
INSERT INTO notes (player_uuid, note_title, note_content, created_at, modified_at, pinned) 
VALUES (:player_uuid, :note_title, :note_content, :created_at, :modified_at, :pinned)
ON CONFLICT(player_uuid, note_title) DO UPDATE SET 
note_content = excluded.note_content, modified_at = excluded.modified_at, pinned = excluded.pinned;
-- #    }

-- #    { select_all
-- #      :player_uuid string
SELECT note_title, note_content, created_at, modified_at, pinned FROM notes WHERE player_uuid = :player_uuid;
-- #    }

-- #    { select_single
-- #      :player_uuid string
-- #      :note_title string
SELECT note_content, created_at, modified_at, pinned FROM notes WHERE player_uuid = :player_uuid AND note_title = :note_title;
-- #    }

-- #    { delete
-- #      :player_uuid string
-- #      :note_title string
DELETE FROM notes WHERE player_uuid = :player_uuid AND note_title = :note_title;
-- #    }

-- #}

-- #!mysql

-- #{ notes

-- #    { create
CREATE TABLE IF NOT EXISTS notes (
    player_uuid VARCHAR(36) NOT NULL,
    note_title VARCHAR(255) NOT NULL,
    note_content TEXT,
    created_at DATETIME,
    modified_at DATETIME,
    pinned TINYINT(1),
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
ON DUPLICATE KEY UPDATE note_content = VALUES(note_content), modified_at = VALUES(modified_at), pinned = VALUES(pinned);
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

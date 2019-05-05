-- # !sqlite

-- #{ hierarchy

-- #  { init
-- #    { rolesTable
CREATE TABLE IF NOT EXISTS Roles
(
    ID        INTEGER PRIMARY KEY AUTOINCREMENT UNIQUE,
    Position  INTEGER      NOT NULL UNIQUE,
    Name      VARCHAR(100) NOT NULL DEFAULT 'new role',
    isDefault BOOLEAN      NOT NULL DEFAULT FALSE
);
-- #    }
-- #    { rolePermissionTable
CREATE TABLE IF NOT EXISTS RolePermissions
(
    RoleID     INTEGER NOT NULL,
    Permission TEXT    NOT NULL,
    PRIMARY KEY (RoleID, Permission)
);
-- #    }
-- #    { memberRolesTable
CREATE TABLE IF NOT EXISTS MemberRoles
(
    Player VARCHAR(16) NOT NULL COLLATE NOCASE, -- MC Only allows IGNs upto 3-16 chars, case in-sensitive.
    RoleID INTEGER     NOT NULL,
    PRIMARY KEY (Player, RoleID)
);
-- #    }
-- #  }

-- # { member
-- #   { roles
-- #     { get
-- #       :username string
SELECT RoleID
FROM MemberRoles
WHERE Player = :username;
-- #     }
-- #     { add
-- #       :username string
-- #       :role_id int
INSERT OR
REPLACE
INTO MemberRoles (Player, RoleID)
VALUES (:username, :role_id);
-- #     }
-- #     { remove
-- #       :username string
-- #       :role_id int
DELETE
FROM MemberRoles
WHERE Player = :username
  AND RoleID = :role_id;
-- #     }
-- #   }
-- # }

-- #  { role
-- #    { list
SELECT *
FROM Roles;
-- #    }
-- #    { create
-- #      :name string
INSERT INTO Roles (Position, Name)
VALUES (IFNULL((SELECT Max(ID) FROM Roles), 0),
        :name);
-- #    }
-- #    { createDefault
-- #      :name string
INSERT INTO Roles (Position, Name, isDefault)
VALUES (IFNULL((SELECT Max(ID) FROM Roles), 0),
        :name, 1);
-- #    }
-- #    { delete
-- #      :role_id int
DELETE
FROM Roles
WHERE ID = :role_id;
-- #    }
-- #    { permissions
-- #      { get
-- #        :role_id int
SELECT Permission
FROM RolePermissions
WHERE RoleID = :role_id;
-- #      }
-- #      { add
-- #        :role_id int
-- #        :permission string
INSERT OR
REPLACE
INTO RolePermissions (RoleID, Permission)
VALUES (:role_id, :permission);
-- #      }
-- #      { remove
-- #        :role_id int
-- #        :permission string
DELETE
FROM RolePermissions
WHERE RoleID = :role_id
  AND Permission LIKE '%' || :permission;
-- #      }
-- #    }
-- #  }

-- #}
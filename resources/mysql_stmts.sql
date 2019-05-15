-- # !mysql

-- #{ hierarchy

-- #  { init
-- #    { rolesTable
CREATE TABLE IF NOT EXISTS Roles
(
    ID        INTEGER PRIMARY KEY AUTO_INCREMENT UNIQUE,
    Position  INTEGER      NOT NULL UNIQUE,
    Name      VARCHAR(100) NOT NULL DEFAULT 'new role',
    isDefault BOOLEAN      NOT NULL DEFAULT 0
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
    Player VARCHAR(16) NOT NULL, -- MC Only allows IGNs upto 3-16 chars, case in-sensitive.
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
SELECT IFNULL(Max(ID), 0), :name
FROM Roles;
-- #    }
-- #    { createDefault
-- #      :name string
INSERT INTO Roles (Position, Name, isDefault)
SELECT IFNULL(Max(ID), 0), :name, 1
FROM Roles;
-- #    }
-- #    { delete
-- #      :role_id int
DELETE
FROM Roles
WHERE ID = :role_id;
-- #    }
-- #    { bumpPosition
-- #      :role_id int
UPDATE Roles
SET Position = Position + 1
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
  AND Permission LIKE CONCAT('%', :permission);
-- #      }
-- #    }
-- #  }

-- #}
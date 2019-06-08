-- # !mysql

-- #{ hierarchy

-- #  { init
-- #    { rolesTable
CREATE TABLE IF NOT EXISTS Roles
(
    ID        INTEGER PRIMARY KEY AUTO_INCREMENT UNIQUE,
    Position  INTEGER      NOT NULL,
    Name      VARCHAR(100) NOT NULL DEFAULT 'new role',
    isDefault BOOLEAN      NOT NULL DEFAULT 0
);
-- #    }
-- #    { rolePermissionTable
CREATE TABLE IF NOT EXISTS RolePermissions
(
    RoleID     INTEGER NOT NULL,
    Permission VARCHAR(128)    NOT NULL,
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
-- #    { memberPermissionsTable
CREATE TABLE IF NOT EXISTS MemberPermissions
(
    Player     VARCHAR(16)  NOT NULL, -- MC Only allows IGNs upto 3-16 chars, case in-sensitive.
    Permission VARCHAR(128) NOT NULL, -- who tf has a permission node with 128 characters anyways?
    PRIMARY KEY (Player, Permission)
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
-- #   { permissions
-- #     { get
-- #       :username string
SELECT Permission
FROM MemberPermissions
WHERE Player = :username;
-- #     }
-- #     { add
-- #       :username string
-- #       :permission string
REPLACE
    INTO MemberPermissions (Player, Permission)
VALUES (:username, :permission);
-- #     }
-- #     { remove
-- #       :username string
-- #       :permission string
DELETE
FROM MemberPermissions
WHERE Player = :username
  AND Permission LIKE CONCAT('%', :permission);
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
-- #      :position int
INSERT INTO Roles (Position, Name)
VALUES (:position, :name);
-- #    }
-- #    { createDefault
-- #      :name string
-- #      :position int
INSERT INTO Roles (Position, Name, isDefault)
VALUES (:position, :name, 1);
-- #    }
-- #    { delete
-- #      :role_id int
DELETE
FROM Roles
WHERE ID = :role_id;
-- #    }
-- #    { position
-- #      { shift
-- #        :offset int
-- #        :amount int
UPDATE Roles
SET Position = Position + :amount
WHERE Position > :offset
ORDER BY Position DESC;
-- #      }
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
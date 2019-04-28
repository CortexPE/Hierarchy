<?php


namespace CortexPE\Hierarchy\data;


class SQLiteDataSource extends SQLDataSource {
	protected const DIALECT = "sqlite";
	protected const STMTS_FILE = "sqlite_stmts.sql";
}
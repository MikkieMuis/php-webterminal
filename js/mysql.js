// mysql.js — fake interactive MySQL/MariaDB client overlay
// Exported globals: mysqlActive, doMysql, mysqlKey

var mysqlActive = false;

// ── Fake database schema ──────────────────────────────────────────────────────

var mysqlDatabases = ['information_schema', 'mysql', 'performance_schema', 'production', 'wordpress'];

var mysqlSchema = {
    information_schema: {
        TABLES:     { cols: ['TABLE_CATALOG','TABLE_SCHEMA','TABLE_NAME','TABLE_TYPE','ENGINE','VERSION','ROW_FORMAT','TABLE_ROWS'], rows: [] },
        COLUMNS:    { cols: ['TABLE_CATALOG','TABLE_SCHEMA','TABLE_NAME','COLUMN_NAME','DATA_TYPE','IS_NULLABLE'], rows: [] },
        USER_PRIVILEGES: { cols: ['GRANTEE','TABLE_CATALOG','PRIVILEGE_TYPE','IS_GRANTABLE'], rows: [["'root'@'localhost'","def","ALL PRIVILEGES","YES"]] }
    },
    mysql: {
        user: {
            cols: ['Host','User','Password','Select_priv','Insert_priv','Update_priv','Delete_priv','Create_priv','Drop_priv','Grant_priv','Super_priv'],
            rows: [
                ['localhost','root','*2470C0C06DEE42FD1618BB99005ADCA2EC9D1E19','Y','Y','Y','Y','Y','Y','Y','Y'],
                ['localhost','mysql.sys','','N','N','N','N','N','N','N','N'],
                ['%','deploy','*A4B6157319038724E3560894F7F932C8886EBFCF','Y','Y','Y','Y','N','N','N','N']
            ]
        },
        db: {
            cols: ['Host','Db','User','Select_priv','Insert_priv','Update_priv','Delete_priv'],
            rows: [
                ['localhost','production','root','Y','Y','Y','Y'],
                ['localhost','wordpress','root','Y','Y','Y','Y'],
                ['%','production','deploy','Y','Y','Y','Y']
            ]
        }
    },
    production: {
        users: {
            cols: ['id','username','email','password_hash','created_at','last_login','role','active'],
            rows: [
                [1,'admin','admin@example.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.','2025-01-15 09:00:00','2026-03-11 14:22:01','admin',1],
                [2,'guest','guest@example.com','$2y$10$TKh8H1.PBR5TFyEAQxQfouQ7B4J3.NcVeXq2M4TlvUXj3AAED0OK2','2025-02-01 10:30:00','2026-03-10 18:45:33','user',1],
                [3,'deploy','deploy@example.com','$2y$10$YBjL1jNY0EEZXQ7IvCHiWuBb.3RKI.4K2hOw4T6G5Dz.H5YxqR22','2025-02-14 08:00:00','2026-03-09 07:10:00','deploy',1],
                [4,'sarah','sarah@example.com','$2y$10$eRx7k3K8bU1nR2fzZP9KbO.qN5yQz1T6H7vJj3M0WoLXiKf9D1Lri','2025-03-01 11:15:00','2026-02-28 20:05:17','user',1],
                [5,'testuser','test@example.com','$2y$10$HgVx3cY5n6L.I7mKa9vHnO8XH2F3Sp7E6Kz4MoRLcQj0Nu5fG9Yq2','2025-06-01 00:00:00',null,'user',0]
            ]
        },
        orders: {
            cols: ['id','user_id','total','status','created_at','updated_at','payment_method','notes'],
            rows: [
                [1,1,'149.99','completed','2026-01-10 10:00:00','2026-01-10 10:45:00','stripe',null],
                [2,2,'29.95','completed','2026-02-01 14:30:00','2026-02-01 14:31:00','paypal',null],
                [3,4,'89.00','pending','2026-02-15 09:20:00','2026-02-15 09:20:00','stripe','Rush order'],
                [4,1,'349.00','completed','2026-02-28 16:00:00','2026-02-28 16:05:00','stripe',null],
                [5,2,'12.50','refunded','2026-03-01 08:00:00','2026-03-02 11:15:00','paypal','Customer request'],
                [6,3,'74.99','processing','2026-03-10 13:45:00','2026-03-10 13:45:00','stripe',null]
            ]
        },
        payments: {
            cols: ['id','order_id','amount','currency','provider','provider_txn_id','status','created_at'],
            rows: [
                [1,1,'149.99','EUR','stripe','ch_1A2B3C4D5E6F7G8H','success','2026-01-10 10:45:00'],
                [2,2,'29.95','EUR','paypal','PAY-9X8Y7Z6W5V4U3T2S','success','2026-02-01 14:31:00'],
                [3,3,'89.00','EUR','stripe','ch_2B3C4D5E6F7G8H9I','pending','2026-02-15 09:20:00'],
                [4,4,'349.00','EUR','stripe','ch_3C4D5E6F7G8H9I0J','success','2026-02-28 16:05:00'],
                [5,5,'12.50','EUR','paypal','PAY-8W7V6U5T4S3R2Q1P','refunded','2026-03-02 11:15:00'],
                [6,6,'74.99','EUR','stripe','ch_4D5E6F7G8H9I0J1K','pending','2026-03-10 13:45:00']
            ]
        }
    },
    wordpress: {
        wp_users: {
            cols: ['ID','user_login','user_email','user_registered','display_name','user_status'],
            rows: [
                [1,'admin','admin@example.com','2024-11-01 09:00:00','Administrator',0],
                [2,'editor','editor@example.com','2025-01-15 10:00:00','Site Editor',0]
            ]
        },
        wp_posts: {
            cols: ['ID','post_author','post_date','post_title','post_status','post_type','comment_count'],
            rows: [
                [1,1,'2024-11-01 10:00:00','Hello World','publish','post',3],
                [2,2,'2025-01-20 14:00:00','Getting Started Guide','publish','post',0],
                [3,1,'2025-02-10 09:00:00','Draft Post','draft','post',0],
                [4,1,'2025-03-01 11:00:00','About Us','publish','page',0]
            ]
        },
        wp_options: {
            cols: ['option_id','option_name','option_value','autoload'],
            rows: [
                [1,'siteurl','https://example.com','yes'],
                [2,'blogname','My WordPress Site','yes'],
                [3,'blogdescription','Just another WordPress site','yes'],
                [4,'admin_email','admin@example.com','yes'],
                [5,'blogpublic','1','yes']
            ]
        }
    }
};

var mysqlDescribe = {
    production: {
        users: [
            ['id','int(11)','NO','PRI',null,'auto_increment'],
            ['username','varchar(64)','NO','UNI',null,''],
            ['email','varchar(255)','NO','UNI',null,''],
            ['password_hash','varchar(255)','NO','','',null,''],
            ['created_at','datetime','NO','','CURRENT_TIMESTAMP',''],
            ['last_login','datetime','YES','',null,''],
            ['role','enum(\'admin\',\'user\',\'deploy\')','NO','','user',''],
            ['active','tinyint(1)','NO','','1','']
        ],
        orders: [
            ['id','int(11)','NO','PRI',null,'auto_increment'],
            ['user_id','int(11)','NO','MUL',null,''],
            ['total','decimal(10,2)','NO','',null,''],
            ['status','enum(\'pending\',\'processing\',\'completed\',\'refunded\')','NO','','pending',''],
            ['created_at','datetime','NO','','CURRENT_TIMESTAMP',''],
            ['updated_at','datetime','YES','',null,''],
            ['payment_method','varchar(32)','NO','',null,''],
            ['notes','text','YES','',null,'']
        ],
        payments: [
            ['id','int(11)','NO','PRI',null,'auto_increment'],
            ['order_id','int(11)','NO','MUL',null,''],
            ['amount','decimal(10,2)','NO','',null,''],
            ['currency','char(3)','NO','','EUR',''],
            ['provider','varchar(32)','NO','',null,''],
            ['provider_txn_id','varchar(128)','YES','',null,''],
            ['status','enum(\'pending\',\'success\',\'failed\',\'refunded\')','NO','','pending',''],
            ['created_at','datetime','NO','','CURRENT_TIMESTAMP','']
        ]
    },
    wordpress: {
        wp_users: [
            ['ID','bigint(20) unsigned','NO','PRI',null,'auto_increment'],
            ['user_login','varchar(60)','NO','MUL','',''],
            ['user_email','varchar(100)','NO','MUL','',''],
            ['user_registered','datetime','NO','','0000-00-00 00:00:00',''],
            ['display_name','varchar(250)','NO','','',''],
            ['user_status','int(11)','NO','','0','']
        ],
        wp_posts: [
            ['ID','bigint(20) unsigned','NO','PRI',null,'auto_increment'],
            ['post_author','bigint(20) unsigned','NO','MUL','0',''],
            ['post_date','datetime','NO','','0000-00-00 00:00:00',''],
            ['post_title','text','NO','','',''],
            ['post_status','varchar(20)','NO','MUL','publish',''],
            ['post_type','varchar(20)','NO','MUL','post',''],
            ['comment_count','bigint(20)','NO','','0','']
        ],
        wp_options: [
            ['option_id','bigint(20) unsigned','NO','PRI',null,'auto_increment'],
            ['option_name','varchar(191)','NO','UNI','',''],
            ['option_value','longtext','NO','','',''],
            ['autoload','varchar(20)','NO','MUL','yes','']
        ]
    }
};

// ── State ─────────────────────────────────────────────────────────────────────

var mysqlData = {
    user:       'root',
    host:       'localhost',
    db:         null,          // currently selected database
    typed:      '',
    history:    [],
    histIdx:    -1,
    multiLine:  '',            // accumulates multi-statement lines ending without ;
    output:     []             // array of {type:'line'|'table', ...}
};

// ── Entry point ───────────────────────────────────────────────────────────────

function doMysql(data) {
    var d = mysqlData;
    d.user     = data.user || 'root';
    d.host     = data.host || 'localhost';
    d.db       = data.db   || null;
    d.typed    = '';
    d.history  = [];
    d.histIdx  = -1;
    d.multiLine = '';
    d.output   = [];

    mysqlActive = true;
    document.getElementById('mysql-overlay').style.display = 'flex';
    hidePrompt();

    mysqlPrint('Welcome to the MariaDB monitor.  Commands end with ; or \\g.');
    mysqlPrint('Your MariaDB connection id is 42');
    mysqlPrint('Server version: 10.5.22-MariaDB MariaDB Server');
    mysqlPrint('');
    mysqlPrint("Copyright (c) 2000, 2018, Oracle, MariaDB Corporation Ab and others.");
    mysqlPrint('');
    mysqlPrint("Type 'help;' or '\\h' for help. Type '\\c' to clear the current input statement.");
    mysqlPrint('');
    mysqlRender();
}

// ── Output helpers ────────────────────────────────────────────────────────────

function mysqlPrint(text, cls) {
    var el = document.getElementById('mysql-output');
    var line = document.createElement('div');
    line.className = 'mysql-line' + (cls ? ' mysql-' + cls : '');
    line.textContent = text;
    el.appendChild(line);
    el.scrollTop = el.scrollHeight;
}

function mysqlClear() {
    document.getElementById('mysql-output').innerHTML = '';
}

function mysqlRender() {
    var d = mysqlData;
    var dbStr = d.db ? d.db : '(none)';
    document.getElementById('mysql-prompt-db').textContent = dbStr;
    document.getElementById('mysql-input').textContent = d.typed;
}

// ── Table rendering ───────────────────────────────────────────────────────────

function mysqlTable(cols, rows) {
    // Calculate column widths
    var widths = cols.map(function(c) { return String(c).length; });
    rows.forEach(function(row) {
        row.forEach(function(cell, i) {
            var len = cell === null ? 4 : String(cell).length;  // NULL = 4 chars
            if (len > widths[i]) widths[i] = len;
        });
    });

    function sep() {
        return '+' + widths.map(function(w) { return '-'.repeat(w + 2); }).join('+') + '+';
    }
    function rowLine(cells) {
        return '| ' + cells.map(function(c, i) {
            var s = c === null ? 'NULL' : String(c);
            return s + ' '.repeat(widths[i] - s.length);
        }).join(' | ') + ' |';
    }

    mysqlPrint(sep());
    mysqlPrint(rowLine(cols));
    mysqlPrint(sep());
    rows.forEach(function(r) { mysqlPrint(rowLine(r)); });
    mysqlPrint(sep());
    var n = rows.length;
    mysqlPrint(n + ' row' + (n === 1 ? '' : 's') + ' in set (0.00 sec)');
    mysqlPrint('');
}

// ── SQL execution ─────────────────────────────────────────────────────────────

function mysqlExec(raw) {
    var d = mysqlData;
    var stmt = raw.trim();
    // strip trailing ; or \g
    stmt = stmt.replace(/[;\s]*\\g\s*$/, '').replace(/;\s*$/, '').trim();

    if (!stmt) { mysqlPrint(''); return; }

    // Normalize to uppercase for keyword matching, but keep original for values
    var up = stmt.toUpperCase().replace(/\s+/g, ' ');

    // QUIT / EXIT / \q
    if (up === 'QUIT' || up === 'EXIT' || stmt === '\\q') {
        mysqlPrint('Bye');
        setTimeout(mysqlClose, 300);
        return;
    }

    // HELP / \h / \?
    if (up === 'HELP' || stmt === '\\h' || stmt === '\\?') {
        mysqlPrint('List of all MySQL commands:');
        mysqlPrint('Note that all text commands must be first on line and end with \';\'');
        mysqlPrint('?         (\\?) Synonym for `help\'.');
        mysqlPrint('clear     (\\c) Clear the current input statement.');
        mysqlPrint('exit      (\\q) Exit mysql. Same as quit.');
        mysqlPrint('help      (\\h) Display this help.');
        mysqlPrint('quit      (\\q) Quit mysql.');
        mysqlPrint('status    (\\s) Get status information from the server.');
        mysqlPrint('use       (\\u) Use another database. Takes database name as argument.');
        mysqlPrint('');
        return;
    }

    // STATUS / \s
    if (up === 'STATUS' || stmt === '\\s') {
        mysqlPrint('--------------');
        mysqlPrint('mysql  Ver 15.1 Distrib 10.5.22-MariaDB, for Linux (x86_64) using  EditLine wrapper');
        mysqlPrint('');
        mysqlPrint('Connection id:          42');
        mysqlPrint('Current database:       ' + (d.db || ''));
        mysqlPrint('Current user:           ' + d.user + '@' + d.host);
        mysqlPrint('SSL:                    Not in use');
        mysqlPrint('Current pager:          stdout');
        mysqlPrint('Using outfile:          \'\'');
        mysqlPrint('Using delimiter:        ;');
        mysqlPrint('Server:                 MariaDB');
        mysqlPrint('Server version:         10.5.22-MariaDB MariaDB Server');
        mysqlPrint('Protocol version:       10');
        mysqlPrint('Connection:             Localhost via UNIX socket');
        mysqlPrint('Server characterset:    utf8mb4');
        mysqlPrint('Db     characterset:    utf8mb4');
        mysqlPrint('Client characterset:    utf8mb3');
        mysqlPrint('Conn.  characterset:    utf8mb3');
        mysqlPrint('UNIX socket:            /var/lib/mysql/mysql.sock');
        mysqlPrint('Uptime:                 21 days 14 hours 7 min 33 sec');
        mysqlPrint('');
        mysqlPrint('Threads: 4  Questions: 18472  Slow queries: 3  Opens: 97  Flush tables: 1  Open tables: 88  Queries per second avg: 0.009');
        mysqlPrint('--------------');
        mysqlPrint('');
        return;
    }

    // CLEAR / \c
    if (up === 'CLEAR' || stmt === '\\c') {
        d.multiLine = '';
        mysqlPrint('');
        return;
    }

    // SHOW DATABASES
    if (up === 'SHOW DATABASES' || up === 'SHOW SCHEMAS') {
        mysqlTable(['Database'], mysqlDatabases.map(function(db) { return [db]; }));
        return;
    }

    // USE db
    var useM = up.match(/^USE\s+(\S+)$/);
    if (useM) {
        var dbName = stmt.replace(/^use\s+/i, '').trim().replace(/;$/, '');
        if (mysqlSchema[dbName] || mysqlDatabases.indexOf(dbName) !== -1) {
            d.db = dbName;
            mysqlPrint('Database changed');
            mysqlPrint('');
        } else {
            mysqlPrint("ERROR 1049 (42000): Unknown database '" + dbName + "'", 'error');
            mysqlPrint('');
        }
        return;
    }

    // SHOW TABLES
    if (up === 'SHOW TABLES' || up.match(/^SHOW TABLES FROM \S+$/)) {
        var fromM = up.match(/^SHOW TABLES FROM (\S+)$/);
        var targetDb = fromM ? stmt.replace(/^show tables from\s+/i,'').trim() : d.db;
        if (!targetDb) { mysqlPrint("ERROR 1046 (3D000): No database selected", 'error'); mysqlPrint(''); return; }
        if (!mysqlSchema[targetDb]) { mysqlPrint("ERROR 1049 (42000): Unknown database '" + targetDb + "'", 'error'); mysqlPrint(''); return; }
        var tbls = Object.keys(mysqlSchema[targetDb]);
        mysqlTable(['Tables_in_' + targetDb], tbls.map(function(t){ return [t]; }));
        return;
    }

    // DESCRIBE / DESC table
    var descM = up.match(/^(DESC(?:RIBE)?)\s+(\S+)$/);
    if (descM) {
        var tblName = stmt.split(/\s+/)[1];
        if (!d.db) { mysqlPrint("ERROR 1046 (3D000): No database selected", 'error'); mysqlPrint(''); return; }
        var defDb = mysqlDescribe[d.db];
        if (!defDb || !defDb[tblName]) {
            mysqlPrint("ERROR 1146 (42S02): Table '" + d.db + "." + tblName + "' doesn't exist", 'error');
            mysqlPrint('');
            return;
        }
        mysqlTable(['Field','Type','Null','Key','Default','Extra'], defDb[tblName]);
        return;
    }

    // SHOW CREATE TABLE
    var sctM = up.match(/^SHOW CREATE TABLE\s+(\S+)$/);
    if (sctM) {
        var tbl2 = stmt.replace(/^show create table\s+/i,'').trim();
        if (!d.db) { mysqlPrint("ERROR 1046 (3D000): No database selected", 'error'); mysqlPrint(''); return; }
        var schDb = mysqlSchema[d.db];
        if (!schDb || !schDb[tbl2]) {
            mysqlPrint("ERROR 1146 (42S02): Table '" + d.db + "." + tbl2 + "' doesn't exist", 'error');
            mysqlPrint('');
            return;
        }
        var descInfo = mysqlDescribe[d.db] && mysqlDescribe[d.db][tbl2];
        var colDefs = descInfo ? descInfo.map(function(r) {
            var def = '  `' + r[0] + '` ' + r[1];
            if (r[2] === 'NO') def += ' NOT NULL';
            if (r[5]) def += ' ' + r[5];
            return def;
        }).join(',\n') : '  -- columns --';
        var createSql = 'CREATE TABLE `' + tbl2 + '` (\n' + colDefs + '\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';
        mysqlTable(['Table','Create Table'], [[tbl2, createSql]]);
        return;
    }

    // SHOW PROCESSLIST
    if (up === 'SHOW PROCESSLIST' || up === 'SHOW FULL PROCESSLIST') {
        mysqlTable(
            ['Id','User','Host','db','Command','Time','State','Info'],
            [
                [1,'system user','','','Daemon',null,'InnoDB purge worker',null],
                [2,'system user','','','Daemon',null,'InnoDB purge worker',null],
                [42,d.user,'localhost',d.db||'','Query',0,'starting','show processlist']
            ]
        );
        return;
    }

    // SHOW STATUS
    if (up === 'SHOW STATUS' || up.match(/^SHOW (?:GLOBAL |SESSION )?STATUS$/)) {
        mysqlTable(['Variable_name','Value'], [
            ['Aborted_clients','0'],['Aborted_connects','3'],['Bytes_received','184920'],
            ['Bytes_sent','2910483'],['Connections','148'],['Created_tmp_tables','52'],
            ['Innodb_buffer_pool_reads','1024'],['Innodb_rows_read','293847'],
            ['Queries','18472'],['Questions','18472'],['Slow_queries','3'],
            ['Threads_connected','2'],['Threads_running','1'],['Uptime','1866453']
        ]);
        return;
    }

    // SHOW VARIABLES [LIKE ...]
    var svM = up.match(/^SHOW (?:GLOBAL |SESSION )?VARIABLES(?:\s+LIKE\s+'([^']*)')?$/);
    if (svM) {
        var allVars = [
            ['auto_increment_increment','1'],['character_set_client','utf8mb3'],
            ['character_set_connection','utf8mb3'],['character_set_database','utf8mb4'],
            ['character_set_results','utf8mb3'],['character_set_server','utf8mb4'],
            ['datadir','/mnt/db/mysql/'],['hostname','localhost'],
            ['innodb_buffer_pool_size','4294967296'],['innodb_log_file_size','536870912'],
            ['max_allowed_packet','67108864'],['max_connections','200'],
            ['port','3306'],['slow_query_log','ON'],
            ['slow_query_log_file','/var/log/mariadb/slow.log'],
            ['socket','/var/lib/mysql/mysql.sock'],['version','10.5.22-MariaDB'],
            ['version_compile_os','Linux'],['wait_timeout','28800']
        ];
        var likeRaw = svM[1];
        var filtered = allVars;
        if (likeRaw) {
            var likeRe = new RegExp('^' + likeRaw.replace(/%/g,'.*').replace(/_/g,'.') + '$', 'i');
            filtered = allVars.filter(function(r){ return likeRe.test(r[0]); });
        }
        mysqlTable(['Variable_name','Value'], filtered);
        return;
    }

    // SELECT — basic implementation
    var selM = up.match(/^SELECT\s+(.+?)\s+FROM\s+(\S+)(.*)?$/);
    if (selM) {
        var selCols  = stmt.match(/^SELECT\s+(.+?)\s+FROM\s+/i)[1].trim();
        var tblRaw   = stmt.match(/FROM\s+(\S+)/i)[1].trim().replace(/;$/, '');
        // support db.table notation
        var selDb = d.db, selTbl = tblRaw;
        if (tblRaw.indexOf('.') !== -1) { selDb = tblRaw.split('.')[0]; selTbl = tblRaw.split('.')[1]; }

        if (!selDb) { mysqlPrint("ERROR 1046 (3D000): No database selected", 'error'); mysqlPrint(''); return; }
        var schemaDb = mysqlSchema[selDb];
        if (!schemaDb) { mysqlPrint("ERROR 1049 (42000): Unknown database '" + selDb + "'", 'error'); mysqlPrint(''); return; }
        var tblObj = schemaDb[selTbl];
        if (!tblObj) { mysqlPrint("ERROR 1146 (42S02): Table '" + selDb + '.' + selTbl + "' doesn't exist", 'error'); mysqlPrint(''); return; }

        var allCols = tblObj.cols;
        var allRows = tblObj.rows.slice();  // copy

        // WHERE (very basic: col = 'val' or col = N)
        var whereM = stmt.match(/WHERE\s+(.+?)(?:\s+LIMIT|\s*;?\s*$)/i);
        if (whereM) {
            var wcond = whereM[1].trim();
            var wcolM = wcond.match(/^(\w+)\s*=\s*['"]?([^'"]+?)['"]?\s*$/i);
            if (wcolM) {
                var wcol = wcolM[1].toLowerCase();
                var wval = wcolM[2];
                var wIdx = allCols.findIndex(function(c){ return c.toLowerCase() === wcol; });
                if (wIdx !== -1) {
                    allRows = allRows.filter(function(r){ return String(r[wIdx]) === wval; });
                }
            }
        }

        // LIMIT
        var limitM = stmt.match(/LIMIT\s+(\d+)/i);
        if (limitM) allRows = allRows.slice(0, parseInt(limitM[1]));

        // COUNT(*)
        if (/^COUNT\s*\(\s*\*\s*\)$/i.test(selCols.trim())) {
            mysqlTable(['COUNT(*)'], [[allRows.length]]);
            return;
        }

        // Column selection
        var outCols, outRows;
        if (selCols.trim() === '*') {
            outCols = allCols;
            outRows = allRows;
        } else {
            var reqCols = selCols.split(',').map(function(c){ return c.trim(); });
            var colIdxs = reqCols.map(function(rc) {
                var idx = allCols.findIndex(function(c){ return c.toLowerCase() === rc.toLowerCase(); });
                return idx;
            });
            var badCol = reqCols.find(function(rc, i){ return colIdxs[i] === -1; });
            if (badCol !== undefined) {
                mysqlPrint("ERROR 1054 (42S22): Unknown column '" + badCol + "' in 'field list'", 'error');
                mysqlPrint('');
                return;
            }
            outCols = reqCols;
            outRows = allRows.map(function(r){ return colIdxs.map(function(i){ return r[i]; }); });
        }

        mysqlTable(outCols, outRows);
        return;
    }

    // INSERT / UPDATE / DELETE — cosmetic acknowledgements
    if (up.match(/^INSERT\s+/)) {
        mysqlPrint('Query OK, 1 row affected (0.01 sec)');
        mysqlPrint('');
        return;
    }
    if (up.match(/^UPDATE\s+/)) {
        mysqlPrint('Query OK, 1 row affected (0.00 sec)');
        mysqlPrint('Rows matched: 1  Changed: 1  Warnings: 0');
        mysqlPrint('');
        return;
    }
    if (up.match(/^DELETE\s+/)) {
        mysqlPrint('Query OK, 1 row affected (0.01 sec)');
        mysqlPrint('');
        return;
    }

    // CREATE / DROP / ALTER — cosmetic
    if (up.match(/^(CREATE|DROP|ALTER|TRUNCATE|RENAME)\s+/)) {
        mysqlPrint('Query OK, 0 rows affected (0.02 sec)');
        mysqlPrint('');
        return;
    }

    // GRANT / REVOKE / FLUSH
    if (up.match(/^(GRANT|REVOKE|FLUSH)\s+/)) {
        mysqlPrint('Query OK, 0 rows affected (0.00 sec)');
        mysqlPrint('');
        return;
    }

    // Unknown
    mysqlPrint("ERROR 1064 (42000): You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near '" + stmt.slice(0, 20) + "' at line 1", 'error');
    mysqlPrint('');
}

// ── Key handler ───────────────────────────────────────────────────────────────

function mysqlKey(key, ctrlKey) {
    if (!mysqlActive) return;
    var d = mysqlData;

    if (ctrlKey) {
        if (key === 'c' || key === 'C') {
            // Ctrl+C — cancel current input
            d.typed = '';
            d.multiLine = '';
            mysqlPrint('');
            mysqlRender();
        } else if (key === 'd' || key === 'D') {
            // Ctrl+D — exit
            mysqlPrint('Bye');
            setTimeout(mysqlClose, 300);
        }
        return;
    }

    if (key === 'Enter') {
        var line = d.typed;
        d.typed = '';
        if (line.trim()) {
            d.history.unshift(line);
            d.histIdx = -1;
        }

        // Print the prompt + input line to output
        var prompt = 'MariaDB [' + (d.db || 'none') + ']> ';
        if (d.multiLine) prompt = '    -> ';
        mysqlPrint(prompt + line);

        // Accumulate multi-line input (no ; yet)
        var full = (d.multiLine ? d.multiLine + ' ' + line : line).trim();
        if (full && !full.match(/[;\\]\s*$/) && !full.match(/^(quit|exit|\\q|\\h|\\?|\\c|\\s)$/i)) {
            d.multiLine = full;
            mysqlRender();
            return;
        }
        d.multiLine = '';
        mysqlExec(full);
        mysqlRender();
        return;
    }

    if (key === 'Backspace') {
        d.typed = d.typed.slice(0, -1);
        mysqlRender();
        return;
    }

    if (key === 'ArrowUp') {
        if (d.history.length === 0) return;
        d.histIdx = Math.min(d.histIdx + 1, d.history.length - 1);
        d.typed = d.history[d.histIdx];
        mysqlRender();
        return;
    }
    if (key === 'ArrowDown') {
        if (d.histIdx <= 0) { d.histIdx = -1; d.typed = ''; mysqlRender(); return; }
        d.histIdx--;
        d.typed = d.history[d.histIdx];
        mysqlRender();
        return;
    }

    if (key === 'Tab') {
        // Basic tab completion for keywords
        var upper = d.typed.toUpperCase();
        var keywords = ['SELECT * FROM ','SHOW TABLES','SHOW DATABASES','DESCRIBE ','USE ','QUIT','EXIT','SHOW PROCESSLIST','SHOW STATUS','SHOW VARIABLES'];
        var match = keywords.find(function(k){ return k.indexOf(upper) === 0 && upper.length > 0 && upper.length < k.length; });
        if (match) { d.typed = match; mysqlRender(); }
        return;
    }

    if (key.length === 1 && !ctrlKey) {
        d.typed += key;
        mysqlRender();
    }
}

// ── Close ─────────────────────────────────────────────────────────────────────

function mysqlClose() {
    mysqlActive = false;
    document.getElementById('mysql-overlay').style.display = 'none';
    print('', 'n');
    updateTitleAndPrompt();
    curline.style.display = 'flex';
    scr.scrollTop = scr.scrollHeight;
}

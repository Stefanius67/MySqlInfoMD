<?php
declare(strict_types=1);

namespace SKien\MySqlTools;

use mysqli;
use mysqli_result;

/**
 * # Generating report for a given MySQL database as MD-Files:
 * overview of all
 * - tables
 * - views
 * - procedures
 * - functions
 * - trigger
 * details for tables
 * - all fields with datatype and keydefinition
 * - defined triggers
 * - defined constraints
 * details for precedures and functions
 * - create statement containing all infos
 *
 * # Supported types
 * - `STANDALONE`
 *   build the MD files for standalone use (File- or Webbased) and view with
 *   browser addon.
 * - `GITHUB_WIKI`
 *   build the MD files to upload to a Github-Wiki
 * - `SKIEN_WIKI`
 *   build the MD files for display inside a SKien-Wiki
 *
 * @author Stefanius <s.kientzler@online.de>
 * @copyright MIT License - see the LICENSE file for details
 */
class MySqlInfoMD
{
    /** build the MD files for standalone use (File- or Webbased)*/
    public const STANDALONE = 0;
    /** build the MD files to upload to a Github-Wiki */
    public const GITHUB_WIKI = 1;
    /** build the MD files for display inside a SKien-Wiki */
    public const SKIEN_WIKI = 2;

    /** Do not create a 'To Overview' link at top of each detail page */
    public const OPT_NO_INDEXLINK = 0x0001;
    /** Create subfolders for Tables, Views, Procs, Funcs  */
    public const OPT_CREATE_SUBFOLDER = 0x0002;
    /** Append the CREATE TABLE/VIEW statement for each table/view after the table structure  */
    public const OPT_CREATE_STMT = 0x0004;
    /** create a table of content file for navigation  */
    public const OPT_CREATE_TOC = 0x0008;

    /** const for the sections     */
    protected const TABLES = 0;
    protected const VIEWS = 1;
    protected const PROCS = 2;
    protected const FUNCS = 3;
    protected const PREFIXES = ['Table_', 'View_', 'Procedure_', 'Function_'];

    /** @var mysqli db connection       */
    protected mysqli $oDB;
    /** @var array<string>  list of tables      */
    protected array $aTables = [];
    /** @var array<string>  list of views       */
    protected array $aViews = [];
    /** @var array<string>  list of procedures      */
    protected array $aProcs = [];
    /** @var array<string>  list of functions       */
    protected array $aFuncs = [];
    /** @var string name of selected database        */
    protected string $strDBName = '';
    /** @var string document root        */
    protected string $strDocRoot = '';
    /** @var string target directory for the MD files         */
    protected string $strPath = '';
    /** @var string image directory         */
    protected string $strImagePath = '';
    /** @var string name of the index file         */
    protected string $strIndex = '';
    /** @var string name of the TOC file         */
    protected string $strTOC = '';
    /** @var array<string> name of subdirs    */
    protected array $aSubDirs;
    /** @var array prefix for the filename of the detailpages for the different db objects    */
    // protected array $aPrefixes = ['Table_', 'View_', 'Procedure_', 'Function_'];
    /** @var int type for the generation  */
    protected int $iType = self::STANDALONE;
    /** @var int options for the generation any combination of the self::OPT_xxx constants         */
    protected int $iOptions = 0;

    /**
     * Create an instance to generate the info MD's.
     * @param mysqli $oDB   The MySQL db ti generate the info for
     * @param int $iType
     * @param int $iOptions Any combination of the self::OPT_xxx constants
     */
    public function __construct(mysqli $oDB, int $iType, int $iOptions = -1)
    {
        $this->oDB = $oDB;

        // by default we're createing the files relative to the current working directory
        $this->strDocRoot = '.';

        // just fetch the database name
        $res = $this->oDB->query("SELECT DATABASE()");
        if ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_NUM)) != false) {
            $this->strDBName = $row[0];
        }
        $this->setType($iType);
        if ($iOptions > 0) {
            $this->setOptions($iOptions);
        }
        // the class creates folders and files in the context of the user 'www-data'. Since we
        // dont want to get a bunch of readonly folders and files, just reset the 'umask'...
        umask(0);
    }

    /**
     * Set the type to generate.
     * @param int $iType
     * @throws \RuntimeException
     */
    public function setType(int $iType) : void
    {
        $this->iType = $iType;
        switch ($iType) {
            case self::STANDALONE:
                $this->aSubDirs = ['Tables', 'Views', 'Procedures', 'Functions'];
                $this->strIndex = 'index';
                $this->strTOC = 'TOC';
                break;
            case self::GITHUB_WIKI:
                $this->aSubDirs = ['', '', '', ''];
                $this->strIndex = 'Home';
                $this->strTOC = '_Sidebar';
                $this->strPath = '';
                $this->strImagePath = '/images';
                break;
            case self::SKIEN_WIKI:
                $this->aSubDirs = ['01_Tables', '02_Views', '03_Routines', '03_Routines'];
                $this->strIndex = 'index';
                $this->strPath = '/wiki';
                $this->strImagePath = '/wiki/images';
                break;
            default:
                throw new \RuntimeException('Invalid Type set: ' . $iType);
        }
    }

    /**
     * Set the options for the info generation.
     * @param int $iOptions Any combination of the self::OPT_xxx constants.
     */
    public function setOptions(int $iOptions) : void
    {
        $this->iOptions = $iOptions;
    }

    /**
     * Set root path from where we are working.
     * By default we're creating the files relative to the current working directory.
     * Note that an absolute path specification at this point is an absolute
     * path on the server!
     * So it can be necessary to use `$_SERVER['DOCUMENT_ROOT']` as starting point,
     * to make the files available through the host.
     * This directory must exist!
     * @param string $strDocRoot
     * @throws \RuntimeException
     */
    public function setRoot(string $strDocRoot) : void
    {
        $this->strDocRoot = rtrim($strDocRoot, '/');
        $this->checkPath($this->strDocRoot);
    }

    /**
     * Path relative to the root path, where the MD files have to be created.
     * The directory will be created, if it doesn't exist!
     * @param string $strPath
     * @throws \RuntimeException
     * @see \SKien\MySqlTools\MySqlInfoMD::setRoot()
     */
    public function setPath(string $strPath) : void
    {
        $this->strPath = trim($strPath, '/');
        $this->checkPath($this->strDocRoot . '/' . $this->strPath, true);
    }

    /**
     * Set path to the used images.
     * For STANDALONE and GITHUB_WIKI, this path is relative to the
     * base path, for SKIEN_WIKI it is seen relative to the root.
     * @param string $strImagePath path.
     * @throws \RuntimeException
     */
    public function setImagePath(string $strImagePath) : void
    {
        $this->strImagePath = trim($strImagePath, '/');
    }

    /**
     * Set the name of the overview file.
     * Default setting is 'index'.
     * For a Github wiki it defaults to 'Home' - set to an other name, if
     * you want to prevent the Github wiki's 'Home.md' from overwriting!
     * (i.e. if the wiki serves other content besides the database structure and
     * thus the database overview is not the general start page of the wiki)
     * @param string $strOverviewName
     */
    public function setOverviewName(string $strOverviewName) : void
    {
        $this->strIndex = pathinfo($strOverviewName, PATHINFO_FILENAME);;
    }

    /**
     * Set the name of the TOC file.
     * Default setting is 'TOC'.
     * For a Github wiki it defaults to '_Sidebar' - set to
     * an other name, if you want to protect the Github wiki's '_Sidebar'!!
     * @param string $strTocName
     * @see \SKien\MySqlTools\MySqlInfoMD::setOverviewName()
     */
    public function setTocName(string $strTocName) : void
    {
        $this->strTOC = pathinfo($strTocName, PATHINFO_FILENAME);
    }

    /**
     * Build the DB information.
     * The overview and all detailpages are generated.
     */
    public function buildInfo() : void
    {
        $this->buildOverview();
        $this->buildTableDetails();
        $this->buildViewDetails();
        $this->buildRoutineDetails();
        $this->buildTOC();
    }

    /**
     * Build the overview file.
     * The filename of the overview can be changed with self::setOverviewName().
     * The overview contains separate lists of all:
     * - tables
     * - views
     * - procedures
     * - functions
     * contained in the database with a link to the corresponding detailpage.
     */
    protected function buildOverview() : void
    {
        $strMD  = '# Database "' . $this->strDBName . '" Overview' . PHP_EOL . PHP_EOL;
        $strMD .= '## Tables' . PHP_EOL . PHP_EOL;
        $strMD .= $this->buildOverviewTableRow([]);

        // SHOW TABLE STATUS returns tables AND views of the database together
        $res = $this->oDB->query("SHOW TABLE STATUS");
        while ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
            if ($row['Comment'] != 'VIEW') {
                $strMD .= $this->buildOverviewTableRow($row);
                $this->aTables[$row['Name']] = trim($row['Comment']);
            } else {
                $this->aViews[] = $row['Name'];
            }
        }
        if (count($this->aViews) > 0) {
            $strMD .= '## Views' . PHP_EOL . PHP_EOL;
            foreach ($this->aViews as $strName) {
                $strMD .= '- ' . $this->getInternalLink($strName, self::VIEWS) . PHP_EOL;
            }
        }

        // ... procedures ...
        $res = $this->oDB->query("SHOW PROCEDURE STATUS WHERE db = '" . $this->strDBName . "' ");
        $bHeader = false;
        if ($res instanceof mysqli_result && $res->num_rows > 0) {
            $strMD .= PHP_EOL . PHP_EOL . '## Routines' . PHP_EOL;
            $bHeader = true;
            $strMD .= $this->buildOverviewRoutinesRow([]);
            while (($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $this->aProcs[$row['Name']] = $row['Comment'];
                $row['Type'] = 'PROCEDURE';
                $strMD .= $this->buildOverviewRoutinesRow($row);
            }
        }

        // ...and functions
        $res = $this->oDB->query("SHOW FUNCTION STATUS WHERE db = '" . $this->strDBName . "' ");
        if ($res instanceof mysqli_result && $res->num_rows > 0) {
            if (!$bHeader) {
                $strMD .= PHP_EOL . PHP_EOL . '## Routines' . PHP_EOL;
                $bHeader = true;
                $strMD .= $this->buildOverviewRoutinesRow([]);
            }
            while (($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $this->aFuncs[$row['Name']] = $row['Comment'];
                $row['Type'] = 'FUNCTION';
                $strMD .= $this->buildOverviewRoutinesRow($row);
            }
        }
        // and write the overview
        $this->writeMarkdown($this->strIndex, $strMD);
    }

    /**
     * Build MD for all table details.
     * - column definition
     * - referenced tables
     * - referencing tables
     * - triggers
     * - TABLE CREATE statement (if configured)
     */
    protected function buildTableDetails() : void
    {
        // get structure of each table
        foreach ($this->aTables as $strName => $strComment) {
            $strMD  = '# Table: ' . $strName . PHP_EOL . PHP_EOL;
            $strMD .= $this->getIndexLink() . PHP_EOL;

            // - comment
            if (!empty($strComment)) {
                $strMD .= '**' . $strComment . '**' . PHP_EOL . PHP_EOL;
            }

            // - columns
            $strMD .= $this->buildTableColumnRow([]);
            $res = $this->oDB->query("SHOW FULL COLUMNS FROM " . $strName);
            while ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= $this->buildTableColumnRow($row);
            }
            // - constraints
            $strMD .= $this->buildReferencedTables($strName);
            $strMD .= $this->buildReferencingTables($strName);

            // - triggers
            $strMD .= $this->buildTriggers($strName);

            // - create statement
            $strMD .= $this->buildTableCreateStatement($strName);

            $this->writeMarkdown($strName, $strMD, self::TABLES);
        }
    }

    /**
     * Build MD for all table details.
     * - column definition
     * - VIEW CREATE statement (if configured)
     */
    protected function buildViewDetails() : void
    {
        foreach ($this->aViews as $strName) {
            $strMD  = '# View: ' . $strName . PHP_EOL . PHP_EOL;
            $strMD .= $this->getIndexLink() . PHP_EOL;

            // - columns
            $strMD .= $this->buildTableColumnRow([]);
            $res = $this->oDB->query("SHOW FULL COLUMNS FROM " . $strName);
            while ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= $this->buildTableColumnRow($row);
            }
            // - create statement
            $strMD .= $this->buildViewCreateStatement($strName);

            $this->writeMarkdown($strName, $strMD, self::VIEWS);
        }
    }

    /**
     * Build a MD containing a table of content
     */
    protected function buildTOC() : void
    {
        if (($this->iOptions & self::OPT_CREATE_TOC) == 0) {
            return;
        }
        $strMD  = '# Database "' . $this->strDBName . '"' . PHP_EOL . PHP_EOL;
        $strMD .= '- ' . $this->getIndexLink(true);
        if (count($this->aTables) > 0) {
            $strMD .= '  - ' . $this->aSubDirs[self::TABLES] . PHP_EOL;
            foreach (array_keys($this->aTables) as $strName) {
                $strMD .= '    - ' . $this->getInternalLink($strName, self::TABLES) . PHP_EOL;
            }
        }
        if (count($this->aViews) > 0) {
            $strMD .= '  - ' . $this->aSubDirs[self::VIEWS] . PHP_EOL;
            foreach ($this->aViews as $strName) {
                $strMD .= '    - ' . $this->getInternalLink($strName, self::VIEWS) . PHP_EOL;
            }
        }
        if (count($this->aProcs) > 0 || count($this->aFuncs) > 0) {
            $strMD .= '  - ' . $this->aSubDirs[self::PROCS] . PHP_EOL;
            foreach (array_keys($this->aProcs) as $strName) {
                $strMD .= '    - ' . $this->getInternalLink($strName, self::PROCS) . PHP_EOL;
            }
            foreach (array_keys($this->aFuncs) as $strName) {
                $strMD .= '    - ' . $this->getInternalLink($strName, self::FUNCS) . PHP_EOL;
            }
        }

        // and write the TOC
        $this->writeMarkdown($this->strTOC, $strMD);
    }

    /**
     * Build MD for all procedures and functions.
     */
    protected function buildRoutineDetails() : void
    {
        foreach ($this->aProcs as $strName => $strComment) {
            $strMD  = '# Procedure: ' . $strName . PHP_EOL . PHP_EOL;
            $strMD .= $this->getIndexLink() . PHP_EOL;
            if (!empty($strComment)) {
                $strMD .= '**' . $strComment . '**' . PHP_EOL;
            }
            $res = $this->oDB->query("SHOW CREATE PROCEDURE " . $strName);
            if ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '```SQL' . PHP_EOL;
                $strMD .= $row['Create Procedure'] . PHP_EOL;
                $strMD .= '```' . PHP_EOL;
            }
            $this->writeMarkdown($strName, $strMD, self::PROCS);
        }
        foreach ($this->aFuncs as $strName => $strComment) {
            $strMD  = '# Function: ' . $strName . PHP_EOL . PHP_EOL;
            $strMD .= $this->getIndexLink() . PHP_EOL;
            if (!empty($strComment)) {
                $strMD .= '**' . $strComment . '**' . PHP_EOL;
            }
            $res = $this->oDB->query("SHOW CREATE FUNCTION " . $strName);
            if ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '```SQL' . PHP_EOL;
                $strMD .= $row['Create Function'] . PHP_EOL;
                $strMD .= '```' . PHP_EOL;
            }
            $this->writeMarkdown($strName, $strMD, self::FUNCS);
        }
    }

    /**
     * Get overview row for tables and views.
     * To create the table header, pass emtpty array to this method.
     * @param array<string> $aRow   rowdata or empty for the table header.
     * @return string
     */
    protected function buildOverviewTableRow(array $aRow) : string
    {
        $strMD = '';
        if (count($aRow) == 0) {
            // create the table header
            $strMD .= $this->getTableRowMD(['Name', 'Engine', 'Rows', 'updated', 'Comment']);
            $strMD .= $this->getTableRowMD(['----', '------', '----', '-------', '-------']);
        } else {
            $strMD .= $this->getTableRowMD([
                $this->getInternalLink($aRow['Name'], self::TABLES),
                $aRow['Engine'],
                $aRow['Rows'],
                $aRow['Update_time'],
                $aRow['Comment'],
            ]);
        }
        return $strMD;
    }

    /**
     * Get overview row for procs and funcs.
     * To create the table header, pass emtpty array to this method.
     * @param array<string> $aRow   rowdata or empty for the table header.
     * @return string
     */
    protected function buildOverviewRoutinesRow(array $aRow) : string
    {
        $strMD = '';
        if (count($aRow) == 0) {
            // create the table header
            $strMD .= $this->getTableRowMD(['Name', 'Type', 'Comment']);
            $strMD .= $this->getTableRowMD(['----', '----', '-------']);
        } else {
            if ($aRow['Type'] == 'FUNCTION') {
                $strLink = $this->getInternalLink($aRow['Name'], self::FUNCS);
            } else {
                $strLink = $this->getInternalLink($aRow['Name'], self::PROCS);
            }
            $strMD .= $this->getTableRowMD([
                $strLink,
                $aRow['Type'],
                $aRow['Comment'],
            ]);
        }
        return $strMD;
    }

    /**
     * Get row for table structure.
     * To create the table header, pass emtpty array to this method.
     * @param array<string> $aRow   rowdata or empty for the table header.
     * @return string
     */
    protected function buildTableColumnRow(array $aRow) : string
    {
        $strMD = '';
        if (count($aRow) == 0) {
            // create the table header
            $strMD .= $this->getTableRowMD(['Field', 'Type', 'Null', 'Key', 'Default', 'Comment']);
            $strMD .= $this->getTableRowMD(['-----', '----', '-', ':-:', '-------', '-------']);
        } else {
            $strMD .= $this->getTableRowMD([
                '`' . $aRow['Field'] . '`',
                $aRow['Type'],
                $this->getNullSymbol($aRow['Null']),
                $this->getKeySymbol($aRow['Key']),
                $this->getColDefault($aRow['Default'], $aRow['Null']),
                $aRow['Comment'],
            ]);
        }
        return $strMD;
    }

    /**
     * Build list of all referenced tables.
     * @param string $strTable
     * @return string
     */
    protected function buildReferencedTables(string $strTable) : string
    {
        $strMD = '';
        $strSQL = $this->getReferencedTablesSQL($strTable);
        $res = $this->oDB->query($strSQL);
        if ($res instanceof mysqli_result && $res->num_rows > 0) {
            $strMD .= PHP_EOL . PHP_EOL . '## References to other Tables' . PHP_EOL;
            $strMD .= '|Column|Reference to|UPDATE|DELETE|' . PHP_EOL;
            $strMD .= '|------|------------|------|------|' . PHP_EOL;
            while (($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '|`' . $row['COLUMN_NAME'] . '`|';
                $strMD .= '`' . $row['REFERENCED_TABLE_NAME'] . '` . `' . $row['REFERENCED_COLUMN_NAME'] . '`|';
                $strMD .= $this->getConstraintRule($row['UPDATE_RULE']) . '|';
                $strMD .= $this->getConstraintRule($row['DELETE_RULE']) . '|' . PHP_EOL;
            }
        }
        return $strMD;
    }

    /**
     * Build list of all referencing tables.
     * @param string $strTable
     * @return string
     */
    protected function buildReferencingTables(string $strTable) : string
    {
        $strMD = '';
        $strSQL = $this->getReferencingTablesSQL($strTable);
        $res = $this->oDB->query($strSQL);
        if ($res instanceof mysqli_result && $res->num_rows > 0) {
            $strMD .= PHP_EOL . PHP_EOL . '## Tables referencing this Table' . PHP_EOL;
            $strMD .= '|Column|Referenced by|UPDATE|DELETE|' . PHP_EOL;
            $strMD .= '|------|-------------|------|------|' . PHP_EOL;
            while (($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '|`' . $row['REFERENCED_COLUMN_NAME'] . '`|';
                $strMD .= '`' . $row['TABLE_NAME'] . '` . `' . $row['COLUMN_NAME'] . '`|';
                $strMD .= $this->getConstraintRule($row['UPDATE_RULE']) . '|';
                $strMD .= $this->getConstraintRule($row['DELETE_RULE']) . '|' . PHP_EOL;
            }
        }
        return $strMD;
    }

    /**
     * Build list of all triggers for given table.
     * @param string $strTable
     * @return string
     */
    protected function buildTriggers(string $strTable) : string
    {
        $strMD = '';
        $res = $this->oDB->query("SHOW TRIGGERS LIKE '" . $strTable . "'");
        if ($res instanceof mysqli_result && $res->num_rows > 0) {
            $strMD .= PHP_EOL . PHP_EOL . '## Trigger' . PHP_EOL;
            while (($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= PHP_EOL . '### ' . $row['Trigger'] . ': ' . $row['Timing'] . ' ' . $row['Event'] . PHP_EOL . PHP_EOL;
                $strMD .= '```SQL' . PHP_EOL;
                $strMD .= $row['Statement'] . PHP_EOL;
                $strMD .= '```' . PHP_EOL;
            }
        }
        return $strMD;
    }

    /**
     * Build the create statement of the table.
     * @param string $strTable
     * @return string
     */
    protected function buildTableCreateStatement(string $strTable) : string
    {
        $strMD = '';
        if (($this->iOptions & self::OPT_CREATE_STMT) != 0) {
            $strMD  = '## Table Create Statement: ' . PHP_EOL . PHP_EOL;
            $res = $this->oDB->query("SHOW CREATE TABLE " . $strTable);
            if ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '```SQL' . PHP_EOL;
                $strMD .= $row['Create Table'] . PHP_EOL;
                $strMD .= '```' . PHP_EOL;
            }
        }
        return $strMD;
    }

    /**
     * Build the create statement of the view.
     * @param string $strView
     * @return string
     */
    protected function buildViewCreateStatement(string $strView) : string
    {
        $strMD = '';
        if (($this->iOptions & self::OPT_CREATE_STMT) != 0) {
            $strMD  = '## View Create Statement: ' . PHP_EOL . PHP_EOL;
            $res = $this->oDB->query("SHOW CREATE VIEW " . $strView);
            if ($res instanceof mysqli_result && ($row = $res->fetch_array(MYSQLI_ASSOC)) != false) {
                $strMD .= '```SQL' . PHP_EOL;
                $strMD .= $row['Create View'] . PHP_EOL;
                $strMD .= '```' . PHP_EOL;
            }
        }
        return $strMD;
    }

    /**
     * Create a table row in (extended) MD format.
     * @param array<string> $aRow
     * @return string
     */
    private function getTableRowMD(array $aRow) : string
    {
        return '|' . implode('|', $aRow) . '|' . PHP_EOL;
    }

    /**
     * Get the internal link to the given page.
     * @param string $strName
     * @param int $iSection
     * @return string
     */
    private function getInternalLink(string $strName, int $iSection) : string
    {
        $strLink = '[' . $strName . ']';
        if ($iSection >= 0) {
            $strName = self::PREFIXES[$iSection] . $strName;
        }
        $strPath = '';
        switch ($this->iType) {
            case self::SKIEN_WIKI:
                // basepath
                $strPath = '/' . $this->strPath . '/';
                if ($this->withSubfolders() && $iSection >= 0) {
                    $strPath .= $this->aSubDirs[$iSection] . '/';
                }
                $strPath .= $strName . '.md';
                break;
            case self::GITHUB_WIKI:
                // all except the images is in the basepath, links without extension
                $strPath = './' . $strName;
                break;
            default:
                //  from basepath...
                $strPath = './';
                if ($this->withSubfolders() && $iSection >= 0) {
                    $strPath .= $this->aSubDirs[$iSection] . '/';
                }
                $strPath .= $strName . '.md';
                break;
        }
        $strLink .= '(' . $strPath . ')';
        return $strLink;
    }

    /**
     * Get a linkt to the overview file.
     * Link is only generated, if option self::OPT_NO_INDEXLINK not set or $bTOC set to true.
     * @param bool $bTOC
     * @return string
     */
    private function getIndexLink(bool $bTOC = false) : string
    {
        $strLink = '';
        if (($this->iOptions & self::OPT_NO_INDEXLINK) == 0 || $bTOC) {
            $strPath = '';
            switch ($this->iType) {
                case self::SKIEN_WIKI:
                    // basepath WITHOUT filename
                    $strPath = '/' . $this->strPath;
                    break;
                case self::GITHUB_WIKI:
                    // indexname without extension
                    $strPath = './' . $this->strIndex;
                    break;
                default:
                    // indexname with extension ...
                    if ($this->withSubfolders()) {
                        $strPath = '../' . $this->strIndex . '.md';
                    } else {
                        $strPath = './' . $this->strIndex . '.md';
                    }
                    break;
            }
            $strLink = '[Overview](' . $strPath . ')' . PHP_EOL;
        }
        return $strLink;
    }

    /**
     * @param string $strRule
     * @return string
     */
    private function getConstraintRule(string $strRule) : string
    {
        return str_replace('NO ACTION', 'RESTRICT', $strRule);
    }

    /**
     * @param string $strNull
     * @return string
     */
    private function getNullSymbol(string $strNull) : string
    {
        $strPath = $this->getImagePath();
        $aNull = [
            'YES' => '![Yes](' . $strPath . '/checked.png "Allows NULL")',
            'NO' => '![No](' . $strPath . '/unchecked.png "Not NULL")',
        ];
        return $aNull[$strNull];
    }

    /**
     * @param string $strKey
     * @return string
     */
    private function getKeySymbol(string $strKey) : string
    {
        $strPath = $this->getImagePath();
        $aKey = [
            '' => '',
            'PRI' => '![PRI](' . $strPath . '/pri_key.png "Primary Key")',
            'UNI' => '![UNI](' . $strPath . '/uni_key.png "Unique Key")',
            'MUL' => '![MUL](' . $strPath . '/mul_key.png "Index")'
        ];
        return $aKey[$strKey];
    }

    /**
     * Get the image path.
     * Images only used in the table/view detail files.
     * @return string
     */
    private function getImagePath() : string
    {
        $strPath = '';
        switch ($this->iType) {
            case self::SKIEN_WIKI:
                // imagepath MUST be absolute from the doc root - so just use it directly!
                $strPath = '/' . $this->strImagePath;
                break;
            case self::GITHUB_WIKI:
                // imagepath is relative to the basepath and all MD files are located in the
                // basepath
                $strPath = './' . ltrim($this->strImagePath, '/');
                break;
            default:
                // imagepath is relative to the basepath and ...
                if ($this->withSubfolders()) {
                    // ... table/view detail MD files are located in a subfolder of the basepath
                    $strPath = '../' . ltrim($this->strImagePath, '/');
                } else {
                    // ... all MD files are located in the
                    $strPath = './' . ltrim($this->strImagePath, '/');
                }
                break;
        }
        return $strPath;
    }

    /**
     * Get displaystring for default value.
     * Use this method to diferenciate between null, empty and not set.
     * @param string $strDefault
     * @param string $strNull
     * @return string
     */
    private function getColDefault(?string $strDefault, string $strNull) : string
    {
        $strDisplay = $strDefault ?? '';
        if ($strDefault === null) {
            $strDisplay = ($strNull == 'YES') ? '*null*' : '*not set*';
        } else if (strlen($strDefault) == 0) {
            $strDisplay = '*empty*';
        }
        return $strDisplay;
    }

    /**
     * Get the SQL statement to retrieve all referenced tables.
     * @param string $strTable
     * @return string
     */
    private function getReferencedTablesSQL(string $strTable) : string
    {
        $strSQL =<<<MYSQL
                SELECT
                  kcu.TABLE_NAME,
                  kcu.COLUMN_NAME,
                  kcu.CONSTRAINT_NAME,
                  kcu.REFERENCED_TABLE_NAME,
                  kcu.REFERENCED_COLUMN_NAME,
                  cref.UPDATE_RULE,
                  cref.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS cref
                ON
                  cref.CONSTRAINT_SCHEMA=kcu.TABLE_SCHEMA AND
                  cref.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
                WHERE
                  kcu.TABLE_SCHEMA = '$this->strDBName' AND
                  kcu.TABLE_NAME = '$strTable' AND
                  kcu.REFERENCED_COLUMN_NAME IS NOT NULL;
            MYSQL;
        return $strSQL;
    }

    /**
     * Get the SQL statement to retrieve all referencing tables.
     * @param string $strTable
     * @return string
     */
    private function getReferencingTablesSQL(string $strTable) : string
    {
        $strSQL =<<<MYSQL
                SELECT
                  kcu.TABLE_NAME,
                  kcu.COLUMN_NAME,
                  kcu.CONSTRAINT_NAME,
                  kcu.REFERENCED_TABLE_NAME,
                  kcu.REFERENCED_COLUMN_NAME,
                  cref.UPDATE_RULE,
                  cref.DELETE_RULE
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS kcu
                LEFT JOIN INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS AS cref
                ON
                  cref.CONSTRAINT_SCHEMA=kcu.TABLE_SCHEMA AND
                  cref.CONSTRAINT_NAME=kcu.CONSTRAINT_NAME
                WHERE
                  kcu.TABLE_SCHEMA = '$this->strDBName' AND
                  kcu.REFERENCED_TABLE_NAME = '$strTable';
            MYSQL;
        return $strSQL;
    }

    /**
     * Checks, if the files are arranged in subfolders.
     * @return bool
     */
    private function withSubfolders() : bool
    {
        $bWithSubfolders = false;
        switch ($this->iType) {
            case self::SKIEN_WIKI:
                // always with subfolders
                $bWithSubfolders = true;
                break;
            case self::GITHUB_WIKI:
                // always without subfolders
                $bWithSubfolders = false;
                break;
            default:
                // depends on options
                $bWithSubfolders = ($this->iOptions & self::OPT_CREATE_SUBFOLDER) != 0;
                break;
        }
        return $bWithSubfolders;
    }

    /**
     * Check the given path.
     * If the path not exist and $bCreate is set, the path will be created.
     * @param string $strPath
     * @param bool $bCreate
     * @throws \RuntimeException
     */
    private function checkPath(string $strPath, bool $bCreate = false) : void
    {
        if (!file_exists($strPath)) {
            if ($bCreate) {
                if (@mkdir($strPath, 0777, true) === false) {
                    $aLastError = error_get_last();
                    throw new \RuntimeException(str_replace('mkdir()', 'mkdir(' . $strPath . ')', $aLastError['message'])); // @phpstan-ignore-line
                }
            } else {
                throw new \RuntimeException($strPath . ' does not exist!');
            }
        } else {
            if (is_dir($strPath) === false) {
                throw new \RuntimeException($strPath . ' is not a directory!');
            }
        }
    }

    /**
     * Write the markdown file for requested section.
     * An existing file will be overwritten.
     * @param string $strName
     * @param string $strMD
     * @param int $iSection
     * @throws \RuntimeException
     */
    private function writeMarkdown(string $strName, string $strMD, int $iSection = -1) : void
    {
        $strPath = $this->strDocRoot . '/' . $this->strPath . '/';
        if ($this->withSubfolders() && $iSection >= 0) {
            $strPath .= $this->aSubDirs[$iSection];
            // check path and create if not exist
            $this->checkPath($strPath, true);
        }
        $strPrefix = ($iSection >= 0) ? self::PREFIXES[$iSection] : '';
        $strFullPath = $strPath . '/' . $strPrefix . $strName . '.md';
        if (@file_put_contents($strFullPath, $strMD) === false) {
            $aLastError = error_get_last();
            throw new \RuntimeException($aLastError['message']); // @phpstan-ignore-line
        }
    }
}

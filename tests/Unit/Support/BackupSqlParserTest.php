<?php

namespace Tests\Unit\Support;

use App\Support\BackupSqlParser;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackupSqlParserTest extends TestCase
{
    #[Test]
    public function it_parses_mysqldump_insert_statements(): void
    {
        $sql = <<<'SQL'
INSERT INTO `group_loans` VALUES
(13932,1140,5798,'4-021-106-4110202-400-087','Women','Agriculture',NULL,'New','defaulted',NULL,NULL,'financed',1,'2014-08-12',NULL,0,100000,100000,30310,'2026-07-06 09:41:35','2026-07-06 11:46:21',1,16,69690.00,100000.00,'2026-07-06 09:41:37');

INSERT INTO `groups` VALUES
(5798,523,'TIENI YOUTH GROUP','tieni-youth-group','106107','087',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-12 14:26:21','2026-06-12 14:26:21');
SQL;

        $parser = new BackupSqlParser();
        $inserts = $parser->parseInserts($sql);

        $this->assertArrayHasKey('group_loans', $inserts);
        $this->assertArrayHasKey('groups', $inserts);
        $this->assertSame('13932', $inserts['group_loans'][0][0]);
        $this->assertSame('TIENI YOUTH GROUP', $inserts['groups'][0][2]);
        $this->assertCount(2, $parser->extractInsertStatements($sql));
    }
}

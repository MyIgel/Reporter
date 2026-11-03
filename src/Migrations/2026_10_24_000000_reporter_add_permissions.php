<?php

declare(strict_types=1);

namespace Engelsystem\Reporter\Migrations;

use Engelsystem\Database\Migration\Migration;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Builder as SchemaBuilder;

class ReporterAddPermissions extends Migration
{
    protected Connection $db;
    protected int $angel = 20;

    public function __construct(SchemaBuilder $schema)
    {
        parent::__construct($schema);
        $this->db = $this->schema->getConnection();
    }

    public function up(): void
    {
        $this->db->table('privileges')
            ->insertOrIgnore([
                'name' => 'report.send',
                'description' => 'Send abuse reports',
            ]);
        $privilege = $this->db->getPdo()->lastInsertId();

        $this->db->table('group_privileges')
            ->insertOrIgnore([
                ['group_id' => $this->angel, 'privilege_id' => $privilege],
            ]);
    }

    public function down(): void
    {
        $this->db->table('privileges')
            ->where('name', 'report.send')
            ->delete();
    }
}

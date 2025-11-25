<?php declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Domains\CoreApp\Migration\MigrationAbstract;

return new class extends MigrationAbstract {
    /**
     * @return void
     */
    public function up(): void
    {
        $this->db()->unprepared('
            ALTER TABLE `alarm_notification`
            DROP COLUMN IF EXISTS `latitude`,
            DROP COLUMN IF EXISTS `longitude`;
        ');

        $this->db()->unprepared('
            ALTER TABLE `alarm_notification`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        $this->db()->unprepared('
            ALTER TABLE `city`
            DROP COLUMN IF EXISTS `latitude`,
            DROP COLUMN IF EXISTS `longitude`;
        ');

        $this->db()->unprepared('
            ALTER TABLE `city`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        $this->db()->unprepared('
            ALTER TABLE `position`
            DROP COLUMN IF EXISTS `latitude`,
            DROP COLUMN IF EXISTS `longitude`;
        ');

        $this->db()->unprepared('
            ALTER TABLE `position`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');

        $this->db()->unprepared('
            ALTER TABLE `refuel`
            DROP COLUMN IF EXISTS `latitude`,
            DROP COLUMN IF EXISTS `longitude`;
        ');

        $this->db()->unprepared('
            ALTER TABLE `refuel`
            ADD COLUMN `latitude` DOUBLE AS (ROUND(ST_Y(`point`), 5)) STORED,
            ADD COLUMN `longitude` DOUBLE AS (ROUND(ST_X(`point`), 5)) STORED;
        ');
    }

    /**
     * @return void
     */
    public function down(): void
    {
        // Nothing to rollback - the migration fixes MariaDB compatibility
        // Rolling back would restore the broken ST_LATITUDE/ST_LONGITUDE functions
        // which don't work in MariaDB
    }
};

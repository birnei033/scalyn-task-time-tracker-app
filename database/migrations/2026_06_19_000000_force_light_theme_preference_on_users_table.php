<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INSERT_TRIGGER = 'users_force_light_theme_preference_before_insert';

    private const UPDATE_TRIGGER = 'users_force_light_theme_preference_before_update';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')->update([
            'theme_preference' => 'light',
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_preference', 20)->nullable()->default('light')->change();
        });

        $this->dropTriggers();
        $this->createTriggers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTriggers();

        Schema::table('users', function (Blueprint $table) {
            $table->string('theme_preference', 20)->nullable()->default(null)->change();
        });

        DB::table('users')->update([
            'theme_preference' => null,
        ]);
    }

    private function createTriggers(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::unprepared(sprintf(
                'CREATE TRIGGER `%s` BEFORE INSERT ON `users` FOR EACH ROW SET NEW.`theme_preference` = \'light\'',
                self::INSERT_TRIGGER,
            ));

            DB::unprepared(sprintf(
                'CREATE TRIGGER `%s` BEFORE UPDATE ON `users` FOR EACH ROW SET NEW.`theme_preference` = \'light\'',
                self::UPDATE_TRIGGER,
            ));

            return;
        }

        if ($driver === 'sqlite') {
            DB::unprepared(sprintf(
                <<<'SQL'
CREATE TRIGGER %s
AFTER INSERT ON users
FOR EACH ROW
WHEN NEW.theme_preference IS NULL OR NEW.theme_preference <> 'light'
BEGIN
    UPDATE users
    SET theme_preference = 'light'
    WHERE id = NEW.id;
END
SQL,
                self::INSERT_TRIGGER,
            ));

            DB::unprepared(sprintf(
                <<<'SQL'
CREATE TRIGGER %s
AFTER UPDATE OF theme_preference ON users
FOR EACH ROW
WHEN NEW.theme_preference IS NULL OR NEW.theme_preference <> 'light'
BEGIN
    UPDATE users
    SET theme_preference = 'light'
    WHERE id = NEW.id;
END
SQL,
                self::UPDATE_TRIGGER,
            ));
        }
    }

    private function dropTriggers(): void
    {
        DB::unprepared(sprintf('DROP TRIGGER IF EXISTS `%s`', self::INSERT_TRIGGER));
        DB::unprepared(sprintf('DROP TRIGGER IF EXISTS `%s`', self::UPDATE_TRIGGER));

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::unprepared(sprintf('DROP TRIGGER IF EXISTS %s', self::INSERT_TRIGGER));
            DB::unprepared(sprintf('DROP TRIGGER IF EXISTS %s', self::UPDATE_TRIGGER));
        }
    }
};

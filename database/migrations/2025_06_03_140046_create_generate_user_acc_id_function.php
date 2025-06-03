<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         DB::unprepared('
            DROP FUNCTION IF EXISTS GenerateUserAccID;

            CREATE FUNCTION GenerateUserAccID() RETURNS VARCHAR(255)
            DETERMINISTIC
            BEGIN
                DECLARE newAccID VARCHAR(255);
                DECLARE maxSuffix INT DEFAULT 0;
                DECLARE suffixStr VARCHAR(10);

                -- Get the highest suffix for the current year
                SELECT 
                    MAX(CAST(SUBSTRING(user_id, 10) AS UNSIGNED))
                INTO maxSuffix
                FROM users
                WHERE YEAR(created_at) = YEAR(CURDATE());

                SET maxSuffix = IFNULL(maxSuffix, 0) + 1;

                -- Format with leading zeroes
                SET suffixStr = LPAD(maxSuffix, 4, "0");

                -- Compose new account ID
                SET newAccID = CONCAT("ACC-", YEAR(CURDATE()), "-", suffixStr);

                RETURN newAccID;
            END;
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP FUNCTION IF EXISTS GenerateUserAccID;');
    }
};

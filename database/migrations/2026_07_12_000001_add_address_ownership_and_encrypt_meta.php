<?php

declare(strict_types=1);

use Capell\Address\Actions\EnsureSiteOwnsAddressAction;
use Capell\Core\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('addresses', function (Blueprint $table): void {
            foreach (['address_part_index', 'address_full_index', 'addresses_state_postal_code_country_id_index'] as $index) {
                if (Schema::hasIndex('addresses', $index)) {
                    $table->dropIndex($index);
                }
            }

            $table->unsignedBigInteger('site_id')->nullable()->index()->after('id');
            $table->text('name')->nullable()->change();
            $table->text('line1')->nullable()->change();
            $table->text('line2')->nullable()->change();
            $table->text('city')->nullable()->change();
            $table->text('state')->nullable()->change();
            $table->text('postal_code')->nullable()->change();
            $table->text('meta')->nullable()->change();
            $table->string('line1_hash', 64)->nullable()->index();
            $table->string('postal_code_hash', 64)->nullable()->index();
            $table->index(['line1_hash', 'postal_code_hash', 'country_id'], 'address_blind_lookup_index');
        });

        $secret = config('app.key');
        throw_unless(is_string($secret) && $secret !== '', RuntimeException::class, 'Address blind-index secret is unavailable.');
        $encryptedColumns = ['name', 'line1', 'line2', 'city', 'state', 'postal_code', 'meta'];

        DB::table('addresses')->orderBy('id')->eachById(function (object $address) use ($encryptedColumns, $secret): void {
            $updates = [];

            foreach ($encryptedColumns as $column) {
                $value = $address->{$column} ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                try {
                    Crypt::decryptString($value);
                } catch (Throwable) {
                    $updates[$column] = Crypt::encryptString($value);
                }
            }

            $updates['line1_hash'] = $this->blindIndex(is_string($address->line1 ?? null) ? $address->line1 : null, $secret);
            $updates['postal_code_hash'] = $this->blindIndex(is_string($address->postal_code ?? null) ? $address->postal_code : null, $secret);
            DB::table('addresses')->where('id', $address->id)->update($updates);
        });

        Site::query()->cursor()->each(static function (Site $site): void {
            EnsureSiteOwnsAddressAction::run($site);
        });
    }

    public function down(): void
    {
        $encryptedColumns = ['name', 'line1', 'line2', 'city', 'state', 'postal_code', 'meta'];

        DB::table('addresses')->orderBy('id')->eachById(function (object $address) use ($encryptedColumns): void {
            $updates = [];

            foreach ($encryptedColumns as $column) {
                $value = $address->{$column} ?? null;

                if (! is_string($value) || $value === '') {
                    continue;
                }

                try {
                    $updates[$column] = Crypt::decryptString($value);
                } catch (Throwable) {
                    $updates[$column] = $value;
                }
            }

            DB::table('addresses')->where('id', $address->id)->update($updates);
        });

        Schema::table('addresses', function (Blueprint $table): void {
            $table->dropIndex('address_blind_lookup_index');
            $table->dropColumn(['site_id', 'line1_hash', 'postal_code_hash']);
        });
    }

    private function blindIndex(?string $value, string $secret): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return hash_hmac('sha256', mb_strtolower(trim($value)), $secret);
    }
};

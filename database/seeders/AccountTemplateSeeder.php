<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Models\AccountTemplate;
use App\Models\AccountTemplateItem;
use Illuminate\Database\Seeder;

class AccountTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $shared = [
            ['110000', 'Aset', AccountType::Asset, null, 1],
            ['110101', 'Kas', AccountType::Asset, '110000', 2],
            ['110201', 'Bank BCA', AccountType::Asset, '110000', 3],
            ['110202', 'Bank Mandiri', AccountType::Asset, '110000', 4],
            ['110501', 'PPN Masukan', AccountType::Asset, '110000', 5],
            ['210000', 'Liabilitas', AccountType::Liability, null, 6],
            ['210101', 'Hutang Usaha', AccountType::Liability, '210000', 7],
            ['210301', 'Kartu Kredit', AccountType::Liability, '210000', 8],
            ['310000', 'Ekuitas', AccountType::Equity, null, 9],
            ['310101', 'Modal Pemilik', AccountType::Equity, '310000', 10],
            ['410000', 'Pendapatan', AccountType::Revenue, null, 11],
            ['410101', 'Pendapatan Jasa', AccountType::Revenue, '410000', 12],
            ['510101', 'HPP', AccountType::Cogs, null, 13],
            ['610000', 'Beban', AccountType::Expense, null, 14],
            ['610101', 'Beban Gaji', AccountType::Expense, '610000', 15],
            ['610102', 'Beban Listrik', AccountType::Expense, '610000', 16],
            ['610103', 'Beban Internet', AccountType::Expense, '610000', 17],
            ['610104', 'Beban BBM', AccountType::Expense, '610000', 18],
            ['610105', 'Beban ATK', AccountType::Expense, '610000', 19],
            ['610106', 'Beban Konsumsi', AccountType::Expense, '610000', 20],
        ];

        $templates = [
            ['umkm', 'UMKM Umum', 'Paket akun umum untuk usaha kecil.'],
            ['jasa', 'Jasa', 'Cocok untuk kantor jasa dan profesional.'],
            ['perdagangan', 'Perdagangan', 'Fokus persediaan dan HPP.'],
            ['fnb', 'F&B', 'Usaha makanan dan minuman.'],
            ['kontraktor', 'Kontraktor', 'Proyek dan persediaan bahan.'],
            ['koperasi', 'Koperasi', 'Simpan pinjam dan operasional koperasi.'],
            ['yayasan', 'Yayasan/Nonprofit', 'Penerimaan donasi dan program.'],
        ];

        foreach ($templates as $index => [$slug, $name, $description]) {
            $template = AccountTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'description' => $description, 'is_active' => true, 'sort_order' => $index + 1],
            );

            $items = $shared;
            if ($slug === 'perdagangan') {
                $items[] = ['110401', 'Persediaan', AccountType::Asset, '110000', 21];
            }
            if ($slug === 'yayasan') {
                $items[] = ['410201', 'Penerimaan Donasi', AccountType::Revenue, '410000', 21];
            }

            foreach ($items as $sort => $item) {
                [$code, $itemName, $type, $parent, $order] = $item;
                AccountTemplateItem::query()->updateOrCreate(
                    ['account_template_id' => $template->id, 'code' => $code],
                    [
                        'name' => $itemName,
                        'type' => $type,
                        'normal_balance' => $type->defaultNormalBalance(),
                        'parent_code' => $parent,
                        'sort_order' => $order,
                    ],
                );
            }
        }
    }
}

<?php

namespace App\Services;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\AccountTemplate;
use App\Models\Workspace;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CoaService
{
    public function copyTemplate(Workspace $workspace, AccountTemplate $template): void
    {
        $items = $template->items()->orderBy('sort_order')->get();
        $byCode = [];

        foreach ($items as $item) {
            $account = Account::query()->create([
                'workspace_id' => $workspace->id,
                'parent_id' => null,
                'code' => $item->code,
                'name' => $item->name,
                'type' => $item->type,
                'normal_balance' => $item->normal_balance,
                'description' => $item->description,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => $item->sort_order,
            ]);
            $byCode[$item->code] = $account;
        }

        foreach ($items as $item) {
            if ($item->parent_code && isset($byCode[$item->parent_code], $byCode[$item->code])) {
                $byCode[$item->code]->update(['parent_id' => $byCode[$item->parent_code]->id]);
            }
        }
    }

    /**
     * @param  array<int, array{code: string, name: string, type: string, parent_code?: string|null}>  $rows
     */
    public function importRows(Workspace $workspace, array $rows): int
    {
        return DB::transaction(function () use ($workspace, $rows) {
            $created = 0;
            $byCode = Account::query()->forWorkspace($workspace)->get()->keyBy('code');

            foreach ($rows as $index => $row) {
                $type = AccountType::from(strtoupper($row['type']));
                $account = Account::query()->updateOrCreate(
                    [
                        'workspace_id' => $workspace->id,
                        'code' => $row['code'],
                    ],
                    [
                        'name' => $row['name'],
                        'type' => $type,
                        'normal_balance' => $type->defaultNormalBalance(),
                        'parent_id' => null,
                        'is_active' => true,
                        'sort_order' => $index,
                    ],
                );
                $byCode[$row['code']] = $account;
                $created++;
            }

            foreach ($rows as $row) {
                $parentCode = $row['parent_code'] ?? null;
                if ($parentCode && isset($byCode[$parentCode], $byCode[$row['code']])) {
                    $byCode[$row['code']]->update(['parent_id' => $byCode[$parentCode]->id]);
                }
            }

            return $created;
        });
    }

    /**
     * @return array<int, array{code: string, name: string, type: string, parent_code: string|null}>
     */
    public function parseSpreadsheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = [];

        foreach ($sheet->toArray() as $index => $row) {
            if ($index === 0) {
                continue;
            }

            $code = trim((string) ($row[0] ?? ''));
            $name = trim((string) ($row[1] ?? ''));
            $type = strtoupper(trim((string) ($row[2] ?? '')));
            $parent = trim((string) ($row[3] ?? ''));

            if ($code === '' || $name === '' || $type === '') {
                continue;
            }

            $rows[] = [
                'code' => $code,
                'name' => $name,
                'type' => $type,
                'parent_code' => $parent === '' ? null : $parent,
            ];
        }

        return $rows;
    }
}

<?php

namespace App\Services;

use App\Models\Interest;
use Illuminate\Support\Collection;

class MessageAudience
{
    public static function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[\s().-]/', '', $phone);
        if (preg_match('/^0([17]\d{8})$/', $phone, $matches)) return '+254'.$matches[1];
        if (preg_match('/^254[17]\d{8}$/', $phone)) return '+'.$phone;
        if (preg_match('/^\+[1-9]\d{7,14}$/', $phone)) return $phone;

        return null;
    }

    public function resolve(array $filters): array
    {
        $query = Interest::query()->whereNotNull('consented_at');
        if ($interest = $filters['interest'] ?? null) $query->where('interest', $interest);
        if ($status = $filters['status'] ?? null) $query->where('status', $status);
        if ($search = $filters['q'] ?? null) {
            $query->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('phone', 'like', '%'.$search.'%'));
        }

        $recipients = new Collection;
        $matched = 0;
        foreach ($query->orderBy('id')->cursor() as $person) {
            $matched++;
            $phone = self::normalizePhone($person->phone);
            if ($phone && ! $recipients->has($phone)) {
                $recipients->put($phone, ['interest_id' => $person->id, 'name' => $person->name, 'phone' => $phone]);
            }
        }

        return ['recipients' => $recipients->values(), 'matched' => $matched, 'excluded' => $matched - $recipients->count()];
    }
}

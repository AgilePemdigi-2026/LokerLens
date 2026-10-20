<?php

namespace App\Exceptions;

use App\Enums\StatusKasus;
use Illuminate\Http\RedirectResponse;
use RuntimeException;

/**
 * Dilempar ketika perubahan status kasus melompat, mundur, atau mengubah status akhir.
 * Pengguna dikembalikan ke halaman sebelumnya dengan pesan error.
 */
class PerubahanStatusTidakValid extends RuntimeException
{
    public static function dari(StatusKasus $asal, StatusKasus $tujuan): self
    {
        $alasan = $asal->isAkhir()
            ? "kasus sudah berstatus akhir \"{$asal->value}\""
            : "status tidak boleh berubah dari \"{$asal->value}\" ke \"{$tujuan->value}\"";

        return new self("Perubahan status ditolak: {$alasan}.");
    }

    public function render(): RedirectResponse
    {
        return back()->withInput()->with('error', $this->getMessage());
    }
}

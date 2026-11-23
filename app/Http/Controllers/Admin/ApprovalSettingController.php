<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApprovalSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApprovalSettingController extends Controller
{
    public function update(Request $request, ApprovalSetting $approvalSetting): RedirectResponse
    {
        $validated = $request->validate([
            'min_amount' => ['required', 'numeric', 'min:0'],
            'is_active'  => ['nullable', 'boolean'],
        ]);

        $approvalSetting->update([
            'min_amount' => (float) $validated['min_amount'],
            'is_active'  => $request->has('is_active')
                ? $request->boolean('is_active')
                : $approvalSetting->is_active,
            'updated_by' => Auth::id(),
        ]);

        ApprovalSetting::clearModuleCache($approvalSetting->module_key);

        return back()->with('status', "Batas minimum persetujuan untuk {$approvalSetting->label} berhasil diperbarui.");
    }
}

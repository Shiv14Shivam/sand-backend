<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AddressController extends Controller
{
    // ── GET /addresses ────────────────────────────────────────────────────────
    public function index()
    {
        $addresses = Address::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json($addresses);
    }

    // ── POST /addresses ───────────────────────────────────────────────────────
    // lat/lng come directly from the Flutter map pin — no geocoding needed.
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'label'          => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'pincode'        => 'required|string|max:10',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'is_default'     => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            Address::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        $address = Address::create([
            'user_id'        => $user->id,
            'label'          => $request->label,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city'           => $request->city,
            'state'          => $request->state,
            'pincode'        => $request->pincode,
            'latitude'       => $request->latitude,
            'longitude'      => $request->longitude,
            'is_default'     => $request->boolean('is_default'),
        ]);

        return response()->json($address, 201);
    }

    // ── PUT /addresses/{id} ───────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'label'          => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'pincode'        => 'required|string|max:10',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'is_default'     => 'nullable|boolean',
        ]);

        if ($request->boolean('is_default')) {
            Address::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update([
            'label'          => $request->label,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city'           => $request->city,
            'state'          => $request->state,
            'pincode'        => $request->pincode,
            // Keep old coords if the user did not re-pin on the map
            'latitude'       => $request->filled('latitude')
                ? $request->latitude
                : $address->latitude,
            'longitude'      => $request->filled('longitude')
                ? $request->longitude
                : $address->longitude,
            'is_default'     => $request->boolean('is_default'),
        ]);

        return response()->json($address->fresh());
    }

    // ── DELETE /addresses/{id} ────────────────────────────────────────────────
    public function destroy($id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }

    // ── POST /addresses/{id}/default ──────────────────────────────────────────
    public function setDefault($id)
    {
        Address::where('user_id', Auth::id())
            ->update(['is_default' => false]);

        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $address->update(['is_default' => true]);

        return response()->json([
            'message' => 'Default address updated',
            'address' => $address,
        ]);
    }

    // ── GET /address/default ──────────────────────────────────────────────────
    public function getDefault()
    {
        $address = Address::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first();

        if (!$address) {
            return response()->json(['message' => 'No default address found'], 404);
        }

        return response()->json($address);
    }
}

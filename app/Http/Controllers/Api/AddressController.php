<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{
    // Get all addresses of logged-in user
    public function index()
    {
        $addresses = Address::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->get();

        return response()->json($addresses);
    }

    // Store a new address
    public function store(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'label'           => 'required|string|max:50',
            'address_line_1'  => 'required|string|max:255',
            'city'            => 'required|string|max:100',
            'state'           => 'required|string|max:100',
            'pincode'         => 'required|string|max:10',
        ]);

        // Combine full address for geocoding
        $fullAddress =
            $request->address_line_1 . ', ' .
            $request->city . ', ' .
            $request->state . ', ' .
            $request->pincode;

        // Call OpenStreetMap Nominatim API
        $lat = null;
        $lng = null;

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'SandApp/1.0'
            ])->timeout(5)->get(
                'https://nominatim.openstreetmap.org/search',
                [
                    'q'      => $fullAddress,
                    'format' => 'json',
                    'limit'  => 1,
                ]
            );

            if ($response->successful() && count($response->json()) > 0) {
                $data = $response->json()[0];
                $lat  = $data['lat'];
                $lng  = $data['lon'];
            }
        } catch (\Exception $e) {
            // Geocoding failed — store address without coordinates
        }

        // If this address is marked default, unset all other defaults for this user
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
            'latitude'       => $lat,
            'longitude'      => $lng,
            'is_default'     => $request->boolean('is_default'),
        ]);

        return response()->json($address, 201);
    }

    // Update an existing address
    public function update(Request $request, $id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $request->validate([
            'label'          => 'required|string|max:50',
            'address_line_1' => 'required|string|max:255',
            'city'           => 'required|string|max:100',
            'state'          => 'required|string|max:100',
            'pincode'        => 'required|string|max:10',
        ]);

        // Re-geocode if address fields changed
        $lat = $address->latitude;
        $lng = $address->longitude;

        $addressChanged =
            $address->address_line_1 !== $request->address_line_1 ||
            $address->city           !== $request->city           ||
            $address->state          !== $request->state          ||
            $address->pincode        !== $request->pincode;

        if ($addressChanged) {
            $fullAddress =
                $request->address_line_1 . ', ' .
                $request->city . ', ' .
                $request->state . ', ' .
                $request->pincode;

            try {
                $geoRes = Http::withHeaders(['User-Agent' => 'SandApp/1.0'])
                    ->timeout(5)
                    ->get('https://nominatim.openstreetmap.org/search', [
                        'q'      => $fullAddress,
                        'format' => 'json',
                        'limit'  => 1,
                    ]);

                if ($geoRes->successful() && count($geoRes->json()) > 0) {
                    $lat = $geoRes->json()[0]['lat'];
                    $lng = $geoRes->json()[0]['lon'];
                }
            } catch (\Exception $e) {
                // Keep old coordinates
            }
        }

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
            'latitude'       => $lat,
            'longitude'      => $lng,
            'is_default'     => $request->boolean('is_default'),
        ]);

        return response()->json($address);
    }

    // Delete an address
    public function destroy($id)
    {
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $address->delete();

        return response()->json(['message' => 'Address deleted successfully']);
    }

    // Set an address as default
    public function setDefault($id)
    {
        // Unset all defaults for this user
        Address::where('user_id', Auth::id())
            ->update(['is_default' => false]);

        // Set the chosen one
        $address = Address::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $address->update(['is_default' => true]);

        return response()->json(['message' => 'Default address updated', 'address' => $address]);
    }

    // Get default address of logged-in user
    public function getDefault()
    {
        $address = Address::where('user_id', Auth::id())
            ->where('is_default', true)
            ->first();

        // FIX: always return proper JSON — never a raw null
        if (!$address) {
            return response()->json(['message' => 'No default address found'], 404);
        }

        return response()->json($address);
    }
}

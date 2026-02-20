<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class AddressController extends Controller
{
    public function store(Request $request)
    {
        $user = Auth::user(); // from Sanctum token

        $address = Address::create([
            'user_id' => $user->id,

            'label' => $request->label,
            'address_line_1' => $request->address_line_1,
            'address_line_2' => $request->address_line_2,
            'city' => $request->city,
            'state' => $request->state,
            'pincode' => $request->pincode,
            'is_default' => $request->is_default ?? false,
        ]);

        return response()->json($address);
    }
    public function index()        // List all addresses of the authenticated user
    {
        return Auth::user()->addresses;
    }

    public function update(Request $request, $id) // Update an address, ensuring it belongs to the authenticated user
    {
        $address = Address::where('id', $id)
            ->where('user_id', auth::id())
            ->firstOrFail();

        $address->update($request->all());

        return response()->json($address);
    }
    public function destroy($id)              // Delete an address, ensuring it belongs to the authenticated user
    {
        Address::where('id', $id)
            ->where('user_id', auth::id())
            ->delete();

        return response()->json(['message' => 'Address deleted']);
    }

    // Set an address as default, ensuring it belongs to the authenticated user
    public function setDefault($id)
    {
        $userId = auth::id();

        Address::where('user_id', $userId)
            ->update(['is_default' => false]);

        Address::where('id', $id)
            ->where('user_id', $userId)
            ->update(['is_default' => true]);

        return response()->json(['message' => 'Default address updated']);
    }
}

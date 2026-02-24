<?php
namespace App\Http\Controllers;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceController extends Controller {
    public function index() {
        $devices = Device::where('company_id', Auth::user()->company_id)->get();
        return view('devices.index', compact('devices'));
    }
    public function store(Request $request) {
        $request->validate(['name' => 'required|string', 'serial_number' => 'required|string|unique:devices,serial_number']);
        Device::create(['name' => $request->name, 'serial_number' => $request->serial_number, 'company_id' => Auth::user()->company_id]);
        return back()->with('success', 'Relógio adicionado! Configure a URL no equipamento.');
    }
    public function destroy(Device $device) {
        if($device->company_id === Auth::user()->company_id) $device->delete();
        return back()->with('success', 'Relógio removido!');
    }
}
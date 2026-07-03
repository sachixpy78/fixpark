<?php

namespace App\Http\Controllers\Api;

use App\Models\Data;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DataController extends Controller
{
    // GET all data
    public function index()
    {
        return response()->json(Data::orderBy('id', 'desc')->get());
    }

    // POST create data
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'nik'     => 'required|unique:data,nik',
            'nama'    => 'required|max:100',
            'alamat'  => 'nullable',
            'no_telp' => 'nullable|max:20', // ✅ field baru
        ]);

        $data = Data::create($validatedData);

        return response()->json([
            'message' => 'Data berhasil ditambahkan',
            'data'    => $data
        ], 201);
    }

    // GET specific data
    public function show($id)
    {
        $data = Data::find($id);

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        return response()->json($data);
    }

    // PUT update data
    public function update(Request $request, $id)
    {
        $data = Data::find($id);

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $validatedData = $request->validate([
            'nik'     => 'required|unique:data,nik,' . $id,
            'nama'    => 'required|max:100',
            'alamat'  => 'nullable',
            'no_telp' => 'nullable|max:20', // ✅ field baru juga di update
        ]);

        $data->update($validatedData);

        return response()->json([
            'message' => 'Data berhasil diupdate',
            'data'    => $data
        ]);
    }

    // DELETE data
    public function destroy($id)
    {
        $data = Data::find($id);

        if (!$data) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $data->delete();

        return response()->json(['message' => 'Data berhasil dihapus']);
    }
}

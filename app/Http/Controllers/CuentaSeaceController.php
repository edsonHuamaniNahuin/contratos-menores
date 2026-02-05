<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CuentaSeace;
use Illuminate\Http\Request;

class CuentaSeaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cuentas = CuentaSeace::orderBy('activa', 'desc')
            ->orderBy('last_login_at', 'desc')
            ->get();

        return view('cuentas.index', compact('cuentas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cuentas.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'required|string|size:11|unique:cuentas_seace,username',
            'password' => 'required|string|min:6',
            'email' => 'nullable|email|max:255',
            'activa' => 'boolean',
        ]);

        // Mapear 'ruc' del formulario a 'username' de la BD
        $data = [
            'nombre' => $validated['nombre'],
            'username' => $validated['ruc'],
            'password' => $validated['password'],
            'email' => $validated['email'] ?? null,
            'activa' => $request->boolean('activa', false),
        ];

        $cuenta = CuentaSeace::create($data);

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(CuentaSeace $cuenta)
    {
        return view('cuentas.show', compact('cuenta'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CuentaSeace $cuenta)
    {
        return view('cuentas.edit', compact('cuenta'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CuentaSeace $cuenta)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'ruc' => 'required|string|size:11|unique:cuentas_seace,username,' . $cuenta->id,
            'password' => 'nullable|string|min:6',
            'email' => 'nullable|email|max:255',
        ]);

        // Mapear 'ruc' del formulario a 'username' de la BD
        $data = [
            'nombre' => $validated['nombre'],
            'username' => $validated['ruc'],
            'email' => $validated['email'] ?? null,
        ];

        // Solo actualizar password si se proporcionó uno nuevo
        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $cuenta->update($data);

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CuentaSeace $cuenta)
    {
        $cuenta->delete();

        return redirect()->route('cuentas.index')
            ->with('success', 'Cuenta eliminada exitosamente.');
    }

    /**
     * Establecer cuenta como principal
     */
    public function setPrincipal(CuentaSeace $cuenta)
    {
        $cuenta->establecerComoPrincipal();

        return redirect()->route('cuentas.index')
            ->with('success', "Cuenta '{$cuenta->nombre}' establecida como principal.");
    }

    /**
     * Toggle estado activo
     */
    public function toggleActiva(CuentaSeace $cuenta)
    {
        $cuenta->update(['activa' => !$cuenta->activa]);

        $estado = $cuenta->activa ? 'activada' : 'desactivada';

        return redirect()->route('cuentas.index')
            ->with('success', "Cuenta {$estado} exitosamente.");
    }
}

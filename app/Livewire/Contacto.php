<?php

namespace App\Livewire;

use App\Mail\ContactoMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

/**
 * Componente Livewire: Formulario de Contacto.
 *
 * Envía un correo a services@sunqupacha.com con los datos
 * ingresados por el visitante/usuario.
 */
class Contacto extends Component
{
    public string $nombre = '';
    public string $email = '';
    public string $asunto = '';
    public string $mensaje = '';

    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    protected function rules(): array
    {
        return [
            'nombre'  => 'required|string|min:2|max:100',
            'email'   => 'required|email|max:150',
            'asunto'  => 'required|string|min:3|max:200',
            'mensaje' => 'required|string|min:10|max:2000',
        ];
    }

    protected function messages(): array
    {
        return [
            'nombre.required'  => 'El nombre es obligatorio.',
            'nombre.min'       => 'El nombre debe tener al menos 2 caracteres.',
            'email.required'   => 'El correo es obligatorio.',
            'email.email'      => 'Ingresa un correo válido.',
            'asunto.required'  => 'El asunto es obligatorio.',
            'asunto.min'       => 'El asunto debe tener al menos 3 caracteres.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'mensaje.min'      => 'El mensaje debe tener al menos 10 caracteres.',
        ];
    }

    public function enviar(): void
    {
        $this->successMessage = null;
        $this->errorMessage = null;

        $this->validate();

        try {
            Mail::to('services@sunqupacha.com')->send(new ContactoMail(
                nombre: $this->nombre,
                email: $this->email,
                asunto: $this->asunto,
                mensajeTexto: $this->mensaje,
            ));

            $this->successMessage = 'Tu mensaje fue enviado correctamente. Te responderemos pronto.';
            $this->reset(['nombre', 'email', 'asunto', 'mensaje']);

            Log::info('Contacto: correo enviado', ['from' => $this->email]);
        } catch (\Exception $e) {
            $this->errorMessage = 'No se pudo enviar el mensaje. Intenta de nuevo más tarde.';
            Log::error('Contacto: error al enviar', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.contacto');
    }
}

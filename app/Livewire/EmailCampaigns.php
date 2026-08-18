<?php

namespace App\Livewire;

use App\Jobs\SendEmailCampaign;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\User;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class EmailCampaigns extends Component
{
    use WithFileUploads;
    // ─── Import/Export ───
    public $importFile = null;
    public string $importType = 'campaign';
    public bool $showImportModal = false;

    // ─── Campaign form ───
    public ?int $editingId = null;
    public string $name = '';
    public string $subject = '';
    public string $body = '';
    public string $filtroTipo = 'todos';
    public array $filtroIds = [];
    public string $blacklistInput = '';
    public array $blacklistIds = [];
    public ?string $scheduledAt = null;
    public string $searchUser = '';

    // ─── Template form ───
    public ?int $editingTemplateId = null;
    public string $templateName = '';
    public string $templateSubject = '';
    public string $templateBody = '';

    // ─── Confirmations ───
    public ?int $confirmDeleteId = null;
    public ?int $confirmSendId = null;
    public ?int $confirmDeleteTplId = null;

    protected $rules = [
        'name' => 'required|string|max:255',
        'subject' => 'required|string|max:255',
        'body' => 'required|string|min:10',
        'templateName' => 'required|string|max:255',
        'templateSubject' => 'required|string|max:255',
        'templateBody' => 'required|string|min:10',
        'filtroTipo' => 'required|in:todos,premium,no-premium,especifico,whatsapp-ventana',
        'scheduledAt' => 'nullable|date|after:now',
    ];

    public function create(): void { $this->editingId = -1; $this->resetForm(); }
    public function createTemplate(): void { $this->editingTemplateId = -1; $this->tpReset(); }

    public function edit(int $id): void
    {
        $c = EmailCampaign::findOrFail($id);
        if (!$c->isEditable()) { $this->dispatch('notify', 'Solo editable en borrador/programada.', 'warning'); return; }
        $this->editingId = $id;
        $this->name = $c->name;
        $this->subject = $c->subject;
        $this->body = $c->body;
        $this->filtroTipo = $c->filtro_tipo;
        $this->filtroIds = $c->filtro_ids ?? [];
        $this->blacklistIds = $c->blacklist_ids ?? [];
        $this->scheduledAt = $c->scheduled_at?->format('Y-m-d\TH:i');
    }

    public function editTemplate(int $id): void
    {
        $t = EmailTemplate::findOrFail($id);
        $this->editingTemplateId = $id;
        $this->templateName = $t->name;
        $this->templateSubject = $t->subject;
        $this->templateBody = $t->body;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'body' => 'required|string|min:10',
            'filtroTipo' => 'required|in:todos,premium,no-premium,especifico,whatsapp-ventana',
            'scheduledAt' => 'nullable|date|after:now',
        ]);

        $data = [
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'filtro_tipo' => $this->filtroTipo,
            'filtro_ids' => $this->filtroTipo === 'especifico' ? $this->filtroIds : null,
            'blacklist_ids' => $this->blacklistIds,
            'scheduled_at' => $this->scheduledAt ?: null,
            'status' => $this->scheduledAt ? EmailCampaign::STATUS_PROGRAMADA : EmailCampaign::STATUS_BORRADOR,
        ];

        if ($this->editingId > 0) {
            EmailCampaign::findOrFail($this->editingId)->update($data);
        } else {
            $data['created_by'] = auth()->id();
            EmailCampaign::create($data);
        }

        $this->editingId = null;
        $this->resetForm();
        $this->dispatch('notify', 'Campaña guardada.', 'success');
    }

    public function saveTemplate(): void
    {
        $this->validate([
            'templateName' => 'required|string|max:255',
            'templateSubject' => 'required|string|max:255',
            'templateBody' => 'required|string|min:10',
        ]);

        if ($this->editingTemplateId > 0) {
            EmailTemplate::findOrFail($this->editingTemplateId)->update([
                'name' => $this->templateName, 'subject' => $this->templateSubject, 'body' => $this->templateBody,
            ]);
        } else {
            EmailTemplate::create([
                'name' => $this->templateName, 'subject' => $this->templateSubject, 'body' => $this->templateBody, 'created_by' => auth()->id(),
            ]);
        }

        $this->editingTemplateId = null;
        $this->tpReset();
        $this->dispatch('notify', 'Plantilla guardada.', 'success');
    }

    public function loadTemplate(int $id): void
    {
        $t = EmailTemplate::findOrFail($id);
        $this->subject = $t->subject;
        $this->body = $t->body;
        $this->dispatch('notify', 'Plantilla cargada: ' . $t->name, 'info');
    }

    public function cancelEdit(): void { $this->editingId = null; $this->resetForm(); }
    public function cancelEditTemplate(): void { $this->editingTemplateId = null; $this->tpReset(); }

    // ─── Actions ───
    public function confirmSend(int $id): void { $this->confirmSendId = $id; }
    public function confirmDelete(int $id): void { $this->confirmDeleteId = $id; }
    public function confirmDeleteTpl(int $id): void { $this->confirmDeleteTplId = $id; }
    public function cancelConfirm(): void { $this->confirmSendId = null; $this->confirmDeleteId = null; $this->confirmDeleteTplId = null; }

    public function sendNow(int $id): void
    {
        $c = EmailCampaign::findOrFail($id);
        $c->update(['status' => EmailCampaign::STATUS_ENVIANDO]);
        if (app()->environment('local')) {
            dispatch_sync(new SendEmailCampaign($c));
            $this->confirmSendId = null;
            $this->dispatch('notify', 'Enviada. ' . $c->fresh()->total_sent . ' correos.', 'success');
        } else {
            SendEmailCampaign::dispatch($c);
            $this->confirmSendId = null;
            $this->dispatch('notify', 'Enviada al queue.', 'success');
        }
    }

    public function delete(int $id): void
    {
        $c = EmailCampaign::findOrFail($id);
        if (!in_array($c->status, [EmailCampaign::STATUS_BORRADOR, EmailCampaign::STATUS_ERROR])) { return; }
        $c->delete();
        $this->confirmDeleteId = null;
        $this->dispatch('notify', 'Campaña eliminada.', 'info');
    }

    public function deleteTemplate(int $id): void
    {
        EmailTemplate::findOrFail($id)->delete();
        $this->confirmDeleteTplId = null;
        $this->dispatch('notify', 'Plantilla eliminada.', 'info');
    }

    public function duplicate(int $id): void
    {
        $o = EmailCampaign::findOrFail($id);
        $n = $o->replicate();
        $n->name = $o->name . ' (copia)';
        $n->status = EmailCampaign::STATUS_BORRADOR;
        $n->sent_at = null; $n->scheduled_at = null; $n->total_sent = 0; $n->total_recipients = 0;
        $n->created_by = auth()->id();
        $n->save();
        $this->dispatch('notify', 'Duplicada como borrador.', 'success');
    }

    // ─── Blacklist ───
    public function addToBlacklist(): void
    {
        $input = trim($this->blacklistInput); if (empty($input)) return;
        $user = null;
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) { $user = User::where('email', $input)->first(); }
        elseif (is_numeric($input)) { $user = User::find($input); }
        if ($user && !in_array($user->id, $this->blacklistIds)) {
            $this->blacklistIds[] = $user->id;
            $this->dispatch('notify', $user->email . ' excluido.', 'info');
        } else { $this->dispatch('notify', 'No encontrado o ya en lista.', 'warning'); }
        $this->blacklistInput = '';
    }

    public function removeFromBlacklist(int $uid): void { $this->blacklistIds = array_values(array_filter($this->blacklistIds, fn($id) => $id !== $uid)); }
    public function addUserId(int $uid): void { if (!in_array($uid, $this->filtroIds)) $this->filtroIds[] = $uid; $this->searchUser = ''; }
    public function removeUserId(int $uid): void { $this->filtroIds = array_values(array_filter($this->filtroIds, fn($id) => $id !== $uid)); }

    // ─── Computed ───
    #[Computed] public function campaigns() { return EmailCampaign::with('creator')->latest()->get(); }
    #[Computed] public function templates() { return EmailTemplate::latest()->get(); }
    #[Computed] public function blacklistUsers() { return empty($this->blacklistIds) ? [] : User::whereIn('id', $this->blacklistIds)->get(['id', 'name', 'email']); }
    #[Computed] public function searchResults() { return strlen($this->searchUser) < 2 ? [] : User::where('name', 'like', '%'.$this->searchUser.'%')->orWhere('email', 'like', '%'.$this->searchUser.'%')->limit(10)->get(['id', 'name', 'email']); }

    // ─── Import/Export ───
    public function toggleImport(string $type): void
    {
        $this->importType = $type;
        $this->importFile = null;
        $this->showImportModal = !$this->showImportModal;
    }

    public function exportCampaign(int $id)
    {
        $c = EmailCampaign::findOrFail($id);
        $this->dispatch('download-json', data: json_encode([
            'type' => 'campaign',
            'name' => $c->name,
            'subject' => $c->subject,
            'body' => $c->body,
            'filtro_tipo' => $c->filtro_tipo,
            'filtro_ids' => $c->filtro_ids,
            'blacklist_ids' => $c->blacklist_ids,
            'scheduled_at' => $c->scheduled_at?->toDateTimeString(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), filename: 'campaña_' . \Illuminate\Support\Str::slug($c->name) . '.json');
    }

    public function exportTemplate(int $id)
    {
        $t = EmailTemplate::findOrFail($id);
        $this->dispatch('download-json', data: json_encode([
            'type' => 'template',
            'name' => $t->name,
            'subject' => $t->subject,
            'body' => $t->body,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), filename: 'plantilla_' . \Illuminate\Support\Str::slug($t->name) . '.json');
    }

    public function import(): void
    {
        $this->validate(['importFile' => 'required|file|mimes:json|max:512']);

        $json = json_decode(file_get_contents($this->importFile->getRealPath()), true);
        if (!$json || empty($json['name']) || empty($json['body'])) {
            $this->dispatch('notify', 'Archivo JSON inválido. Debe contener al menos name y body.', 'error');
            return;
        }

        if (($json['type'] ?? '') === 'template' || $this->importType === 'template') {
            EmailTemplate::create([
                'name' => ($json['name'] ?? '') . ' (importada)',
                'subject' => $json['subject'] ?? '',
                'body' => $json['body'] ?? '',
                'created_by' => auth()->id(),
            ]);
            $this->dispatch('notify', 'Plantilla importada correctamente.', 'success');
        } else {
            EmailCampaign::create([
                'name' => ($json['name'] ?? '') . ' (importada)',
                'subject' => $json['subject'] ?? '',
                'body' => $json['body'] ?? '',
                'filtro_tipo' => $json['filtro_tipo'] ?? 'todos',
                'filtro_ids' => $json['filtro_ids'] ?? null,
                'blacklist_ids' => $json['blacklist_ids'] ?? null,
                'scheduled_at' => isset($json['scheduled_at']) ? $json['scheduled_at'] : null,
                'status' => EmailCampaign::STATUS_BORRADOR,
                'created_by' => auth()->id(),
            ]);
            $this->dispatch('notify', 'Campaña importada correctamente.', 'success');
        }

        $this->showImportModal = false;
        $this->importFile = null;
    }

    public function render() { return view('livewire.email-campaigns'); }

    private function resetForm(): void
    {
        $this->name = ''; $this->subject = ''; $this->body = ''; $this->filtroTipo = 'todos';
        $this->filtroIds = []; $this->blacklistInput = ''; $this->blacklistIds = []; $this->scheduledAt = null; $this->searchUser = '';
    }
    private function tpReset(): void { $this->templateName = ''; $this->templateSubject = ''; $this->templateBody = ''; }
}

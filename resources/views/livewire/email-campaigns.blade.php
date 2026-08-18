<div class="p-4 lg:p-6 space-y-6">
    <style>
        trix-toolbar [data-trix-button-group="file-tools"] { display: none !important; }
        .trix-content { min-height: 200px; }
        .trix-content ul { list-style: disc; padding-left: 1.5rem; }
        .trix-content ol { list-style: decimal; padding-left: 1.5rem; }
        .trix-content a { color: #025964; text-decoration: underline; }
    </style>

    <div class="flex items-center justify-between">
        <div><h1 class="text-xl lg:text-2xl font-bold text-neutral-900">Gestión de Correos</h1><p class="text-sm text-neutral-500">Campañas de email y plantillas reutilizables</p></div>
        <div class="flex items-center gap-2">
            <button wire:click="createTemplate" class="inline-flex items-center gap-2 px-4 py-2.5 border border-neutral-200 text-neutral-700 text-sm font-medium rounded-full hover:bg-neutral-50"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>Nueva plantilla</button>
            <button wire:click="toggleImport('campaign')" class="inline-flex items-center gap-1.5 px-4 py-2.5 border border-neutral-200 text-neutral-600 text-sm font-medium rounded-full hover:bg-neutral-50"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg>Importar</button>
            <button wire:click="create" class="inline-flex items-center gap-2 px-5 py-2.5 bg-primary-500 text-white text-sm font-bold rounded-full hover:bg-primary-400 shadow-sm"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Nueva campaña</button>
        </div>
    </div>

    {{-- Template list --}}
    @if(count($this->templates) > 0)
        <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 overflow-hidden">
            <div class="px-5 py-3 border-b border-neutral-100 bg-neutral-50"><h3 class="text-sm font-bold text-neutral-700">Plantillas ({{ count($this->templates) }})</h3></div>
            <table class="w-full text-sm"><tbody class="divide-y divide-neutral-50">
                @foreach($this->templates as $tp)
                <tr class="hover:bg-neutral-50/50">
                    <td class="px-5 py-3"><p class="font-semibold text-neutral-900">{{ $tp->name }}</p><p class="text-xs text-neutral-400 truncate max-w-md">{{ $tp->subject }}</p></td>
                    <td class="px-5 py-3 text-xs text-neutral-400">{{ $tp->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="#" wire:click.prevent="exportTemplate({{ $tp->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-400 hover:text-primary-600 hover:border-primary-300" title="Exportar"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg></a>
                            <button wire:click="loadTemplate({{ $tp->id }}, true)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary-50 text-primary-600 hover:bg-primary-100" title="Usar en campaña"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg></button>
                            <button wire:click="editTemplate({{ $tp->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-500 hover:text-primary-600 hover:border-primary-300" title="Editar"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>
                            <button wire:click="confirmDeleteTpl({{ $tp->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-400 hover:text-red-600 hover:border-red-300" title="Eliminar"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody></table>
        </div>
    @endif

    {{-- Campaign list --}}
    <div class="bg-white rounded-2xl shadow-soft border border-neutral-100 overflow-hidden">
        @if(count($this->campaigns) === 0)
            <div class="p-12 text-center text-neutral-400 text-sm">No hay campañas creadas aún.</div>
        @else
            <table class="w-full text-sm"><thead class="bg-neutral-50 border-b border-neutral-100"><tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Campaña</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-neutral-500 uppercase">Destino</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase">Estado</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase">Fecha</th>
                <th class="px-4 py-3 text-center text-xs font-semibold text-neutral-500 uppercase">Acciones</th>
            </tr></thead><tbody class="divide-y divide-neutral-50">
            @foreach($this->campaigns as $c)
            <tr class="hover:bg-neutral-50/50">
                <td class="px-4 py-3"><p class="font-semibold text-neutral-900">{{ $c->name }}</p><p class="text-xs text-neutral-400">{{ $c->subject }}</p></td>
                <td class="px-4 py-3 text-xs text-neutral-600">@php $fm=['todos'=>'Todos','premium'=>'Premium','no-premium'=>'No-Premium','especifico'=>count($c->filtro_ids??[]).' usuarios','whatsapp-ventana'=>'WhatsApp: ventana 24h vencida']; @endphp {{ $fm[$c->filtro_tipo]??'-' }}</td>
                <td class="px-4 py-3 text-center">@php $sc=['borrador'=>'bg-neutral-100 text-neutral-600','programada'=>'bg-blue-50 text-blue-700','enviando'=>'bg-amber-50 text-amber-700','enviada'=>'bg-green-50 text-green-700','error'=>'bg-red-50 text-red-600']; @endphp <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $sc[$c->status]??'' }}">{{ ucfirst($c->status) }}</span></td>
                <td class="px-4 py-3 text-center text-xs text-neutral-500">@if($c->sent_at) {{ $c->sent_at->format('d/m/Y H:i') }} @elseif($c->scheduled_at) Prog: {{ $c->scheduled_at->format('d/m/Y H:i') }} @else {{ $c->created_at->format('d/m/Y') }} @endif</td>
                <td class="px-4 py-3"><div class="flex items-center justify-center gap-1.5">
                    @if(in_array($c->status,['borrador','programada']))<button wire:click="edit({{ $c->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-500 hover:text-primary-600 hover:border-primary-300" title="Editar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></button>@endif
                    @if(in_array($c->status,['borrador','programada']))<button wire:click="confirmSend({{ $c->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg bg-primary-500 text-white hover:bg-primary-400" title="Enviar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></button>@endif
                    <button wire:click="duplicate({{ $c->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-500 hover:text-neutral-700" title="Duplicar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg></button>
                    <a href="#" wire:click.prevent="exportCampaign({{ $c->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-neutral-200 text-neutral-400 hover:text-primary-600 hover:border-primary-300" title="Exportar JSON"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 4v11"/></svg></a>
                    @if(in_array($c->status,['borrador','error']))<button wire:click="confirmDelete({{ $c->id }})" class="w-8 h-8 flex items-center justify-center rounded-lg border border-red-200 text-red-500 hover:bg-red-50" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>@endif
                </div></td>
            </tr>
            @endforeach
            </tbody></table>
        @endif
    </div>

    {{-- =========================== CAMPAIGN MODAL =========================== --}}
    @if($editingId !== null)
        @php $isEdit = $editingId > 0; @endphp
        <div class="fixed inset-0 z-[130] flex items-start justify-center pt-12 px-4 pb-8" x-data>
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelEdit"></div>
            <div class="relative w-full max-w-2xl max-h-[85vh] bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 shrink-0">
                    <h2 class="text-lg font-bold text-neutral-900">{{ $isEdit ? 'Editar campaña' : 'Nueva campaña' }}</h2>
                    <button wire:click="cancelEdit" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900">Cerrar</button>
                </div>
                <form wire:submit="save" x-data x-on:submit="document.getElementById('trix-ta').dispatchEvent(new Event('input',{bubbles:true}))" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Nombre</label>
                        <input type="text" wire:model="name" class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Asunto @if(count($this->templates)>0)<span class="font-normal text-neutral-400"> — <select wire:change="loadTemplate($event.target.value)" class="text-[11px] bg-neutral-50 border border-neutral-200 rounded-lg px-2 py-1"><option value="">cargar plantilla…</option>@foreach($this->templates as $tp)<option value="{{ $tp->id }}">{{ $tp->name }}</option>@endforeach</select></span>@endif</label>
                        <input type="text" wire:model="subject" class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('subject') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Cuerpo del correo</label>
                        <textarea wire:model="body" id="trix-ta" class="hidden">{{ $body }}</textarea>
                        <trix-editor input="trix-ta" class="bg-white rounded-xl border border-neutral-200"></trix-editor>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] text-neutral-400 mr-1">Tags:</span>
                            @foreach(['nombre' => 'FULL NAME', 'email' => 'EMAIL', 'plan' => 'PLAN', 'dias_restantes' => 'DIAS RESTANTES'] as $tag => $label)
                                <button type="button" onclick="insertTag('trix-ta','{{ $tag }}')" class="px-2.5 py-1 bg-neutral-100 hover:bg-primary-50 hover:text-primary-600 text-[10px] font-semibold rounded-full border border-neutral-200 transition-colors">{{ $label }}</button>
                            @endforeach
                        </div>
                        @error('body') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    {{-- Destinatarios --}}
                    <div class="border-t border-neutral-100 pt-4">
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Destinatarios</h3>
                        <div class="flex flex-wrap gap-2 mb-3">
                            @foreach(['todos'=>'Todos','premium'=>'Premium','no-premium'=>'No-Premium','especifico'=>'Específicos','whatsapp-ventana'=>'WhatsApp: ventana 24h vencida'] as $v=>$l)
                                <button type="button" wire:click="$set('filtroTipo','{{ $v }}')" class="px-3 py-1.5 rounded-full text-xs font-semibold {{ $filtroTipo===$v?'bg-primary-500 text-white':'bg-neutral-100 text-neutral-600 hover:bg-neutral-200'}}">{{ $l }}</button>
                            @endforeach
                        </div>
                        @if($filtroTipo === 'especifico')
                            <input type="text" wire:model.live.debounce.300ms="searchUser" placeholder="Buscar usuario..." class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm mb-2 focus:outline-none focus:ring-2 focus:ring-primary-500">
                            @if(count($this->searchResults)>0)<div class="bg-white border border-neutral-200 rounded-xl shadow-lg max-h-40 overflow-y-auto mb-2">@foreach($this->searchResults as $sr)<button type="button" wire:click="addUserId({{ $sr->id }})" class="w-full px-4 py-2 text-left text-sm hover:bg-neutral-50 flex justify-between"><span>{{ $sr->name }}</span><span class="text-xs text-neutral-400">{{ $sr->email }}</span></button>@endforeach</div>@endif
                            @if(count($filtroIds)>0)<div class="flex flex-wrap gap-1">@foreach(\App\Models\User::whereIn('id',$filtroIds)->get() as $u)<span class="inline-flex items-center gap-1 px-2 py-1 bg-primary-50 text-primary-700 text-xs rounded-full">{{ $u->email }}<button type="button" wire:click="removeUserId({{ $u->id }})" class="text-red-400 hover:text-red-600">&times;</button></span>@endforeach</div>@endif
                        @endif
                    </div>
                    {{-- Blacklist --}}
                    <div class="border-t border-neutral-100 pt-4">
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Lista negra</h3>
                        <div class="flex gap-2 mb-2"><input type="text" wire:model="blacklistInput" placeholder="Email o ID a excluir" class="flex-1 px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500"><button type="button" wire:click="addToBlacklist" class="px-4 py-2.5 bg-red-50 text-red-600 text-xs font-bold rounded-full hover:bg-red-100">+ Excluir</button></div>
                        @if(count($this->blacklistUsers)>0)<div class="flex flex-wrap gap-1">@foreach($this->blacklistUsers as $bu)<span class="inline-flex items-center gap-1 px-2 py-1 bg-red-50 text-red-700 text-xs rounded-full">{{ $bu->email }}<button type="button" wire:click="removeFromBlacklist({{ $bu->id }})" class="text-red-400 hover:text-red-600">&times;</button></span>@endforeach</div>@endif
                    </div>
                    {{-- Schedule --}}
                    <div class="border-t border-neutral-100 pt-4">
                        <h3 class="text-sm font-bold text-neutral-900 mb-2">Programar (opcional)</h3>
                        <input type="datetime-local" wire:model="scheduledAt" class="px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="px-6 py-3 bg-primary-500 text-white text-sm font-bold rounded-full hover:bg-primary-400">Guardar campaña</button>
                        <button type="button" wire:click="cancelEdit" class="px-6 py-3 border border-neutral-200 text-neutral-600 text-sm font-medium rounded-full hover:bg-neutral-50">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- =========================== TEMPLATE MODAL =========================== --}}
    @if($editingTemplateId !== null)
        <div class="fixed inset-0 z-[130] flex items-start justify-center pt-12 px-4 pb-8" x-data>
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelEditTemplate"></div>
            <div class="relative w-full max-w-2xl max-h-[85vh] bg-white rounded-[2rem] shadow-soft border border-neutral-200 flex flex-col overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-neutral-100 shrink-0">
                    <h2 class="text-lg font-bold text-neutral-900">{{ $editingTemplateId > 0 ? 'Editar plantilla' : 'Nueva plantilla' }}</h2>
                    <button wire:click="cancelEditTemplate" class="px-4 py-2 text-xs font-semibold rounded-full border border-neutral-200 text-neutral-600 hover:text-neutral-900">Cerrar</button>
                </div>
                <form wire:submit="saveTemplate" x-data x-on:submit="document.getElementById('trix-tpl-ta').dispatchEvent(new Event('input',{bubbles:true}))" class="flex-1 overflow-y-auto p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Nombre</label>
                        <input type="text" wire:model="templateName" class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('templateName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Asunto</label>
                        <input type="text" wire:model="templateSubject" class="w-full px-4 py-2.5 bg-neutral-50 border border-neutral-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500">
                        @error('templateSubject') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Cuerpo</label>
                        <textarea wire:model="templateBody" id="trix-tpl-ta" class="hidden">{{ $templateBody }}</textarea>
                        <trix-editor input="trix-tpl-ta" class="bg-white rounded-xl border border-neutral-200"></trix-editor>
                        <div class="flex flex-wrap items-center gap-1.5 mt-2">
                            <span class="text-[10px] text-neutral-400 mr-1">Tags:</span>
                            @foreach(['nombre' => 'FULL NAME', 'email' => 'EMAIL', 'plan' => 'PLAN', 'dias_restantes' => 'DIAS RESTANTES'] as $tag => $label)
                                <button type="button" onclick="insertTag('trix-tpl-ta','{{ $tag }}')" class="px-2.5 py-1 bg-neutral-100 hover:bg-primary-50 hover:text-primary-600 text-[10px] font-semibold rounded-full border border-neutral-200 transition-colors">{{ $label }}</button>
                            @endforeach
                        </div>
                        @error('templateBody') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button type="submit" class="px-6 py-3 bg-primary-500 text-white text-sm font-bold rounded-full hover:bg-primary-400">Guardar plantilla</button>
                        <button type="button" wire:click="cancelEditTemplate" class="px-6 py-3 border border-neutral-200 text-neutral-600 text-sm font-medium rounded-full hover:bg-neutral-50">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- =========================== CONFIRMATION MODALS =========================== --}}
    @if($confirmSendId) @php $sc=$this->campaigns->firstWhere('id',$confirmSendId); @endphp @if($sc)
    <div class="fixed inset-0 z-[140] flex items-center justify-center px-4" x-data><div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelConfirm"></div>
        <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-8 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-primary-100 flex items-center justify-center"><svg class="w-7 h-7 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></div>
            <h3 class="text-lg font-bold text-neutral-900 mb-1">¿Enviar ahora?</h3><p class="text-sm text-neutral-500 mb-5"><strong>{{ $sc->name }}</strong> — {{ match($sc->filtro_tipo){'todos'=>'todos','premium'=>'Premium','no-premium'=>'No-Premium','especifico'=>count($sc->filtro_ids??[]).' usuarios',default=>'—'} }}</p>
            <div class="flex gap-3"><button wire:click="cancelConfirm" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50">Cancelar</button><button wire:click="sendNow({{ $sc->id }})" class="flex-1 py-2.5 bg-primary-500 text-white rounded-full text-sm font-bold hover:bg-primary-400">Enviar</button></div>
        </div>
    </div>@endif @endif

    @if($confirmDeleteId) @php $dc=$this->campaigns->firstWhere('id',$confirmDeleteId); @endphp @if($dc)
    <div class="fixed inset-0 z-[140] flex items-center justify-center px-4" x-data><div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelConfirm"></div>
        <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft p-8 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
            <h3 class="text-lg font-bold text-neutral-900 mb-1">¿Eliminar?</h3><p class="text-sm text-neutral-500 mb-5"><strong>{{ $dc->name }}</strong></p>
            <div class="flex gap-3"><button wire:click="cancelConfirm" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50">Cancelar</button><button wire:click="delete({{ $dc->id }})" class="flex-1 py-2.5 bg-red-500 text-white rounded-full text-sm font-bold hover:bg-red-400">Eliminar</button></div>
        </div>
    </div>@endif @endif

    @if($confirmDeleteTplId) @php $dt=$this->templates->firstWhere('id',$confirmDeleteTplId); @endphp @if($dt)
    <div class="fixed inset-0 z-[140] flex items-center justify-center px-4" x-data><div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="cancelConfirm"></div>
        <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft p-8 text-center">
            <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-red-100 flex items-center justify-center"><svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></div>
            <h3 class="text-lg font-bold text-neutral-900 mb-1">¿Eliminar plantilla?</h3><p class="text-sm text-neutral-500 mb-5"><strong>{{ $dt->name }}</strong></p>
            <div class="flex gap-3"><button wire:click="cancelConfirm" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50">Cancelar</button><button wire:click="deleteTemplate({{ $dt->id }})" class="flex-1 py-2.5 bg-red-500 text-white rounded-full text-sm font-bold hover:bg-red-400">Eliminar</button></div>
        </div>
    </div>        @endif @endif

    {{-- Import Modal --}}
    @if($showImportModal)
        <div class="fixed inset-0 z-[140] flex items-center justify-center px-4" x-data>
            <div class="absolute inset-0 bg-neutral-900/60 backdrop-blur-sm" wire:click="toggleImport('')"></div>
            <div class="relative w-full max-w-sm bg-white rounded-[2rem] shadow-soft border border-neutral-200 p-6">
                <h3 class="text-lg font-bold text-neutral-900 mb-4">Importar {{ $importType === 'template' ? 'plantilla' : 'campaña' }}</h3>
                <form wire:submit="import">
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-neutral-600 mb-1">Archivo JSON</label>
                        <input type="file" wire:model="importFile" accept=".json" class="w-full text-sm text-neutral-600 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100 file:cursor-pointer">
                        @error('importFile') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" wire:click="toggleImport('')" class="flex-1 py-2.5 border border-neutral-200 text-neutral-600 rounded-full text-sm font-semibold hover:bg-neutral-50">Cancelar</button>
                        <button type="submit" class="flex-1 py-2.5 bg-primary-500 text-white rounded-full text-sm font-bold hover:bg-primary-400">Importar</button>
                    </div>
                </form>
                <p class="text-[10px] text-neutral-400 mt-3 text-center">Formatos aceptados: JSON exportado desde Gestión de Correos</p>
            </div>
        </div>
    @endif

<script>
window.addEventListener('download-json', function(e) {
    var blob = new Blob([e.detail.data], {type: 'application/json'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = e.detail.filename || 'export.json';
    a.click();
    URL.revokeObjectURL(a.href);
});

function insertTag(inputId, tag) {
    var editor = document.querySelector('trix-editor[input="' + inputId + '"]');
    if (!editor) return;
    editor.editor.insertString('@{{ ' + tag + ' }}');
}

// Sincronizar el contenido del editor Trix cuando Livewire carga una plantilla
window.addEventListener('trix-cargar', function(e) {
    var inputId = e.detail.inputId;
    var input = document.getElementById(inputId);
    var editor = document.querySelector('trix-editor[input="' + inputId + '"]');
    if (input && editor && typeof editor.editor.loadHTML === 'function') {
        editor.editor.loadHTML(input.value);
    }
});
</script>
</div>

<x-app-layout>
    <x-page-header
        breadcrumb="Configurações <span class='text-gray-400 mx-1'>›</span> Escola"
        title="Cadastro da Escola"
        subtitle="Configure os dados e a logo que aparecerão no cabeçalho dos documentos PEI e PAEE."
    />

    <x-flash-messages />

    <div class="max-w-2xl">
        <x-form-card title="Dados da Escola">
            @if($school->logo_path)
                <div class="mb-6 p-4 bg-gray-50 rounded-lg inline-block">
                    <img src="{{ asset('storage/' . $school->logo_path) }}" alt="Logo" class="h-16 object-contain">
                </div>
            @endif

            <form method="POST" action="{{ route('schools.update') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf @method('PUT')

                <div>
                    <label class="plannia-label" for="name">Nome da escola <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text" class="plannia-input" value="{{ old('name', $school->name) }}" required>
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="address">Endereço</label>
                    <textarea id="address" name="address" rows="2" class="plannia-input resize-none">{{ old('address', $school->address) }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-1" />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="plannia-label" for="cnpj">CNPJ</label>
                        <input id="cnpj" name="cnpj" type="text" class="plannia-input" value="{{ old('cnpj', $school->cnpj) }}" placeholder="00.000.000/0000-00">
                        <x-input-error :messages="$errors->get('cnpj')" class="mt-1" />
                    </div>
                    <div>
                        <label class="plannia-label" for="inep">Código INEP</label>
                        <input id="inep" name="inep" type="text" class="plannia-input" value="{{ old('inep', $school->inep) }}" placeholder="Ex: 12345678">
                        <x-input-error :messages="$errors->get('inep')" class="mt-1" />
                    </div>
                </div>
                <div>
                    <label class="plannia-label" for="phone">Telefone</label>
                    <input id="phone" name="phone" type="text" class="plannia-input" value="{{ old('phone', $school->phone) }}" placeholder="(00) 0000-0000">
                    <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="logo">Logo (aparece no PEI e PAEE)</label>
                    <input id="logo" name="logo" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-plannia-blue" accept="image/*">
                    <p class="mt-1 text-xs text-gray-500">Logo, nome, CNPJ, telefone e INEP aparecem no cabeçalho dos documentos gerados.</p>
                    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="plannia-btn-primary">Salvar</button>
                </div>
            </form>
        </x-form-card>
    </div>
</x-app-layout>

<x-app-layout>
    <x-page-header
        breadcrumb="Configurações <span class='text-gray-400 mx-1'>›</span> Escola"
        title="Cadastro da Escola"
        subtitle="Configure os dados e a logo que aparecerá nos documentos PEI e PAEE."
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
                <div>
                    <label class="plannia-label" for="cnpj">CNPJ</label>
                    <input id="cnpj" name="cnpj" type="text" class="plannia-input" value="{{ old('cnpj', $school->cnpj) }}">
                    <x-input-error :messages="$errors->get('cnpj')" class="mt-1" />
                </div>
                <div>
                    <label class="plannia-label" for="logo">Logo (aparece no PEI e PAEE)</label>
                    <input id="logo" name="logo" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-plannia-blue" accept="image/*">
                    <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="plannia-btn-primary">Salvar</button>
                </div>
            </form>
        </x-form-card>
    </div>
</x-app-layout>

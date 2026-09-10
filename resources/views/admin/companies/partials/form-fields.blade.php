<div class="company-form-grid">
    <div class="company-field wide">
        <label>Nome</label>
        <input name="name" value="{{ old('name', $company->name ?? '') }}" required>
    </div>
    <div class="company-field">
        <label>Localizacao</label>
        <input name="location" value="{{ old('location', $company->location ?? '') }}">
    </div>
    <div class="company-field">
        <label>NIF</label>
        <input name="nif" value="{{ old('nif', $company->nif ?? '') }}">
    </div>
    <div class="company-field">
        <label>Banco</label>
        <input name="bank_name" value="{{ old('bank_name', $company->bank_name ?? '') }}">
    </div>
    <div class="company-field">
        <label>IBAN</label>
        <input name="iban" value="{{ old('iban', $company->iban ?? '') }}">
    </div>
    <div class="company-field">
        <label>Conta bancaria</label>
        <input name="account_number" value="{{ old('account_number', $company->account_number ?? '') }}">
    </div>
    <div class="company-field">
        <label>SWIFT</label>
        <input name="swift" value="{{ old('swift', $company->swift ?? '') }}">
    </div>
    <div class="company-field">
        <label>Logotipo</label>
        <input name="logo" type="file" accept="image/*">
    </div>
    <div class="company-field">
        <label>Fundo do login</label>
        <input name="login_background" type="file" accept="image/*">
    </div>
</div>

<label class="company-check">
    <input type="checkbox" name="active" value="1" checked>
    Empresa ativa
</label>

<label class="company-check" style="{{ $prefix === 'edit' ? 'display:none;' : 'display:none;' }}">
    <input type="checkbox" name="remove_logo" value="1">
    Remover logotipo atual
</label>

<label class="company-check" style="{{ $prefix === 'edit' ? 'display:none;' : 'display:none;' }}">
    <input type="checkbox" name="remove_login_background" value="1">
    Remover fundo atual
</label>

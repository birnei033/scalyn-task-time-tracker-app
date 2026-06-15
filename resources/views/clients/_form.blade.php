@php($client = $client ?? new \App\Models\Client)

<div class="row g-3">
    <div class="col-lg-6">
        <label class="form-label">Client Name</label>
        <input class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $client->name) }}" required>
        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Company</label>
        <input class="form-control @error('company') is-invalid @enderror" name="company" value="{{ old('company', $client->company) }}">
        @error('company')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Contact Person</label>
        <input class="form-control @error('contact_person') is-invalid @enderror" name="contact_person" value="{{ old('contact_person', $client->contact_person) }}">
        @error('contact_person')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-6">
        <label class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $client->email) }}">
        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-lg-4">
        <label class="form-label">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" name="status">
            @foreach (['active' => 'Active', 'archived' => 'Archived'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $client->status ?: 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
    <div class="col-12">
        <label class="form-label">Notes</label>
        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes', $client->notes) }}</textarea>
        @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>

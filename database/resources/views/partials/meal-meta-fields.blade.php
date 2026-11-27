{{--
    Judul, tanggal, jam, catatan. Variabel: $defaults
--}}
<div class="space-y-4">
    <x-field label="Judul" name="title" :value="old('title', $defaults['title'])" placeholder="Contoh: Nasi goreng telur" maxlength="150" required />

    <div class="grid grid-cols-2 gap-3">
        <x-field label="Tanggal" name="eaten_date" type="date" :value="old('eaten_date', $defaults['eaten_date'])" max="{{ today()->toDateString() }}" required />
        <x-field label="Jam makan" name="eaten_time" type="time" :value="old('eaten_time', $defaults['eaten_time'])" required />
    </div>

    <x-field label="Catatan (opsional)" name="notes" :value="old('notes', $defaults['notes'])" maxlength="255" placeholder="Misalnya: makan di kantin, tanpa sambal" />
</div>

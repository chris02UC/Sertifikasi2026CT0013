<?php

/*
 * Indonesian validation messages for the rules this application uses.
 */
return [
    'after' => ':attribute harus setelah :date.',
    'after_or_equal' => ':attribute tidak boleh sebelum hari ini.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'date' => ':attribute harus berupa tanggal.',
    'digits' => ':attribute harus :digits digit angka.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'image' => ':attribute harus berupa gambar.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'max' => [
        'file' => ':attribute maksimal :max kilobyte.',
        'numeric' => ':attribute maksimal :max.',
        'string' => ':attribute maksimal :max karakter.',
    ],
    'mimes' => ':attribute harus berformat: :values.',
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'required_if' => ':attribute wajib diisi.',
    'required_unless' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'unique' => ':attribute sudah terdaftar.',

    'attributes' => [
        'nik' => 'NIK',
        'name' => 'Nama lengkap',
        'phone' => 'Nomor telepon',
        'email' => 'Email',
        'password' => 'Password',
        'vehicle_type' => 'Tipe kendaraan',
        'plate_number' => 'Nomor plat',
        'brand_model' => 'Merek dan tipe',
        'battery_kwh' => 'Kapasitas listrik',
        'rental_type' => 'Tipe sewa',
        'days' => 'Jumlah hari',
        'start_date' => 'Tanggal mulai sewa',
        'garage_type' => 'Tipe garasi',
        'garage_id' => 'Garasi',
        'payment_proof' => 'Bukti pembayaran',
        'type' => 'Tipe garasi',
        'count' => 'Jumlah garasi',
        'daily_price' => 'Harga sewa harian',
        'kwh_price' => 'Harga isi listrik per kWh',
        'new_end_date' => 'Tanggal selesai baru',
    ],
];

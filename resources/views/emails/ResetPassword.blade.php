@component('mail::message')
Terimakasih telah menggunakan layanan OCR Far Capital<br>
untuk mengubah password anda, silahkan klik tombol dibawah ini.
@component('mail::button', ['url' => $target])
Ganti Password
@endcomponent
<p>Link ini akan kadaluarsa dalam 1 jam, untuk mendapatkan link baru kunjungi <a href="{{$from}}">{{$from}}</a></p>
Thanks,<br>
{{ config('app.name') }}
@endcomponent

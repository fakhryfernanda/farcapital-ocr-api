@component('mail::message')
Terimakasih telah menggunakan layanan OCR Farcapital<br>
untuk konfirmasi email anda, silahkan klik tombol dibawah ini.
@component('mail::button', ['url' => $target])
Ganti Password
@endcomponent
<p>Link ini akan kadaluarsa dalam 6 jam, untuk mendapatkan link baru kunjungi <a href="{{$from}}">{{$from}}</a></p>
Thanks,<br>
{{ config('app.name') }}
@endcomponent

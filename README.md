## Route API

POST | localhost:8000/api/login
GET | localhost:8000/api/me

GET | localhost:8000/api/user/ | tampilkan semua user
GET | localhost:8000/api/user/{id} | tampilkan user berdasarkan id
POST | localhost:8000/api/user/add | tambah user / registrasi
POST | localhost:8000/api/user/{id}/edit | edit user berdasarkan id
POST | localhost:8000/api/user/{id}/delete | hapus user berdasarkan id

POST | localhost:8000/api/upload | konversi foto menjadi text dengan ocr
POST | localhost:8000/api/identity/add | tambah / edit identitas user


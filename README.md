# USM Service: asas projek

Lokasi aplikasi XAMPP: C:/xampp/htdocs/usm-service
URL: http://localhost/usm-service/

- config/database.php menyediakan $pdo (PDO, utf8mb4, timezone +08:00).
- index.php ialah semakan sementara localhost; ia hanya membaca jumlah rekod.
- Database yang digunakan: usm-service. Tiada import atau perubahan data dilakukan.
- Sambungan default XAMPP: 127.0.0.1:3306, root, password kosong.
  Jika tetapan berbeza, gunakan USM_DB_HOST, USM_DB_PORT, USM_DB_USER,
  USM_DB_PASSWORD dalam environment pelayan. Jangan commit password sebenar.
- Folder includes, services, reports, assets/css dan assets/js untuk modul seterusnya.
- Login/logout belum diimplementasikan. Akaun admin tidak diubah oleh setup ini.
- Gantikan index.php dengan redirect login/dashboard apabila modul login siap.

Edit aplikasi dalam folder XAMPP untuk pembangunan seterusnya. Salinan dalam
workspace ini ialah sumber setup awal; ia tidak sync secara automatik.

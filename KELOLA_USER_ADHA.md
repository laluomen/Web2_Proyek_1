# 📚 KELOLA USER - Dokumentasi

**Dibuat oleh: Muhammad Adha**  
**Tanggal: 19 Februari 2026**

---

## 📋 Deskripsi

Sistem Kelola User adalah modul manajemen pengguna untuk aplikasi Peminjaman Ruangan. Modul ini memungkinkan administrator untuk mengelola (Create, Read, Update, Delete) data pengguna sistem, baik admin maupun mahasiswa.

## 📁 Struktur File

```
src/admin/
├── kelola_user.php      # Interface/Tampilan kelola user
└── proses_user.php      # Backend processing CRUD operations
```

---

## 🎯 Fitur Utama

### 1. **kelola_user.php** - User Interface
File ini merupakan halaman tampilan untuk manajemen user dengan fitur:

#### 🖼️ Tampilan
- **Header Modern**: Dark gradient dengan tombol "Tambah User" berwarna hijau
- **Search Bar**: Pencarian real-time berdasarkan nama dan username
- **Tabel User**: Menampilkan daftar user dengan informasi lengkap
- **Avatar Circle**: Initial nama user dalam lingkaran berwarna
- **Badge Role**: Visual indicator untuk membedakan Admin dan Mahasiswa
- **Action Buttons**: Detail (biru), Edit (kuning), Hapus (merah)

#### 📊 Kolom Tabel
| Kolom | Deskripsi |
|-------|-----------|
| No | Nomor urut |
| Nama | Nama lengkap user dengan avatar dan tanggal registrasi |
| Username | Username untuk login |
| Role | Badge Admin (purple) atau Mahasiswa (cyan) |
| Prodi | Program studi (khusus mahasiswa) |
| Aksi | Tombol Detail, Edit, Hapus |

#### 🎨 Modal Windows

##### 1. Modal Tambah User (Hijau)
- **Nama Lengkap**: Input text (required)
- **Username**: Input text (required)
- **Password**: Input password (required, min 6 karakter)
- **Role**: Select (Admin/Mahasiswa) (required)
- **Program Studi**: Input text (muncul otomatis jika role Mahasiswa)

##### 2. Modal Edit User (Kuning)
- **Nama Lengkap**: Pre-filled
- **Username**: Pre-filled
- **Password Baru**: Optional (kosongkan jika tidak ingin ubah password)
- **Role**: Pre-selected
- **Program Studi**: Pre-filled (jika mahasiswa)

##### 3. Modal Detail User (Cyan)
Menampilkan informasi lengkap:
- Nama Lengkap
- Username
- Role (dengan badge)
- Program Studi
- Terdaftar Sejak

#### ⚡ JavaScript Functions

```javascript
// Search functionality
searchInput.addEventListener('keyup', ...)

// Toggle prodi field based on role
toggleProdiField(mode)

// Edit user - populate modal
editUser(id, nama, username, role, prodi)

// View detail - show modal
viewDetail(id, nama, username, role, prodi, created)

// Delete with confirmation
deleteUser(id, nama)

// Auto-dismiss alerts (5 seconds)
// Reset form when modal closed
```

---

### 2. **proses_user.php** - Backend Processing
File ini menangani semua operasi CRUD untuk user.

#### 🔐 Security Features
- **Session Validation**: Hanya admin yang dapat akses
- **Password Hashing**: Menggunakan bcrypt (PASSWORD_DEFAULT)
- **SQL Injection Protection**: Prepared statements
- **XSS Protection**: htmlspecialchars pada output
- **Self-Delete Protection**: Admin tidak bisa hapus akun sendiri

#### 📝 Operations

##### 1. **ADD USER** (`?action=add`)
**Method**: POST

**Input Fields**:
```php
$nama       // string, required
$username   // string, required, unique
$password   // string, required
$role       // enum('admin','mahasiswa'), required
$prodi      // string, optional (for mahasiswa)
```

**Process**:
1. Validasi semua field required
2. Cek apakah username sudah digunakan
3. Hash password menggunakan `password_hash()`
4. Insert ke database
5. Redirect dengan success message

**Redirect**:
- Success: `kelola_user.php?success=add`
- Error: `kelola_user.php?error=[pesan]`

---

##### 2. **EDIT USER** (`?action=edit`)
**Method**: POST

**Input Fields**:
```php
$id         // int, required (hidden input)
$nama       // string, required
$username   // string, required, unique (exclude current user)
$password   // string, optional
$role       // enum('admin','mahasiswa'), required
$prodi      // string, optional
```

**Process**:
1. Validasi data tidak lengkap
2. Cek username tidak dipakai user lain (exclude user saat ini)
3. Jika password diisi → hash dan update password
4. Jika password kosong → update tanpa mengubah password
5. Update database
6. Redirect dengan success message

**SQL Logic**:
```php
// Dengan password baru
UPDATE users SET nama=?, username=?, password=?, role=?, prodi=? WHERE id=?

// Tanpa update password
UPDATE users SET nama=?, username=?, role=?, prodi=? WHERE id=?
```

**Redirect**:
- Success: `kelola_user.php?success=edit`
- Error: `kelola_user.php?error=[pesan]`

---

##### 3. **DELETE USER** (`?action=delete`)
**Method**: GET

**Input Parameters**:
```php
$id  // int, required (from URL query string)
```

**Process**:
1. Validasi ID valid
2. **Proteksi**: Cek apakah ID === current user session
3. Jika ya → throw exception "Tidak dapat menghapus akun sendiri!"
4. Jika tidak → DELETE dari database
5. Redirect dengan success message

**Security Note**: 
⚠️ Admin tidak bisa menghapus akun mereka sendiri untuk mencegah kehilangan akses sistem.

**Redirect**:
- Success: `kelola_user.php?success=delete`
- Error: `kelola_user.php?error=[pesan]`

---

## 🗄️ Database Schema

### Tabel: `users`

```sql
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','mahasiswa') NOT NULL,
  `prodi` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### Field Descriptions:
| Field | Type | Constraint | Description |
|-------|------|------------|-------------|
| id | int | PRIMARY KEY, AUTO_INCREMENT | User ID |
| nama | varchar(100) | NOT NULL | Nama lengkap user |
| username | varchar(50) | NOT NULL, UNIQUE | Username untuk login |
| password | varchar(255) | NOT NULL | Password (bcrypt hashed) |
| role | enum | NOT NULL | 'admin' atau 'mahasiswa' |
| prodi | varchar(100) | NULL | Program studi (khusus mahasiswa) |
| created_at | timestamp | DEFAULT CURRENT_TIMESTAMP | Waktu registrasi |

---

## 🎨 Design System

### Color Palette

```css
/* Primary Green */
--green-primary: #22c55e
--green-dark: #16a34a
--green-darker: #15803d

/* Action Colors */
--info-cyan: #0dcaf0
--warning-yellow: #fbbf24
--danger-red: #ef4444

/* Role Badge Colors */
--admin-purple: #667eea → #764ba2 (gradient)
--mahasiswa-cyan: #0dcaf0 → #0aa2c0 (gradient)

/* Dark Theme */
--bg-dark-1: #2d3748
--bg-dark-2: #1a202c
```

### Button Styles

```css
/* Tambah User Button */
.btn-tambah {
  background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
  border-radius: 10px;
  padding: 12px 32px;
  box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
}

/* Detail Button */
.btn-info {
  background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
}

/* Edit Button */
.btn-warning {
  background: linear-gradient(135deg, #fbbf24, #f59e0b);
}

/* Hapus Button */
.btn-danger {
  background: linear-gradient(135deg, #ef4444, #dc2626);
}

/* Batal Button */
background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
```

---

## 🔒 Security Implementation

### 1. Password Hashing (Bcrypt)
```php
// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Verify password (saat login)
password_verify($input_password, $hashedPassword);
```

**Algorithm**: Bcrypt dengan cost factor 10 (default)  
**Format**: `$2y$10$[22-character salt][31-character hash]`

### 2. SQL Injection Prevention
```php
// Prepared Statements
$stmt = query("SELECT * FROM users WHERE username = ?", [$username]);
```

### 3. XSS Protection
```php
// Output escaping
<?= htmlspecialchars($user['nama']) ?>
<?= htmlspecialchars($user['username'], ENT_QUOTES) ?>
```

### 4. Session Management
```php
// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}
```

---

## 📱 Responsive Design

### Breakpoints

```css
/* Mobile (≤480px) */
- Header padding: 20px
- Font size: 22px
- Button full width

/* Tablet (≤768px) */
- Header flex-direction: column
- Header font: 26px
- Center alignment

/* Desktop (>768px) */
- Header horizontal layout
- Full features enabled
```

---

## 🚀 Usage Flow

### Workflow Tambah User

```
1. Admin klik tombol "Tambah User" (hijau)
   ↓
2. Modal Add User muncul
   ↓
3. Admin mengisi form:
   - Nama Lengkap
   - Username
   - Password
   - Role (Admin/Mahasiswa)
   - Prodi (jika mahasiswa)
   ↓
4. Submit form ke proses_user.php?action=add
   ↓
5. Validasi & insert ke database
   ↓
6. Redirect ke kelola_user.php?success=add
   ↓
7. Alert hijau "User berhasil ditambahkan"
   ↓
8. Tabel refresh dengan data baru (paling atas)
```

### Workflow Edit User

```
1. Admin klik tombol "Edit" (kuning) pada baris user
   ↓
2. JavaScript function editUser() dipanggil
   ↓
3. Modal Edit User muncul dengan data pre-filled
   ↓
4. Admin mengubah data yang diperlukan
   ↓
5. Submit form ke proses_user.php?action=edit
   ↓
6. Validasi & update database
   ↓
7. Redirect ke kelola_user.php?success=edit
   ↓
8. Alert hijau "User berhasil diperbarui"
   ↓
9. Tabel refresh dengan data terupdate
```

### Workflow Hapus User

```
1. Admin klik tombol "Hapus" (merah) pada baris user
   ↓
2. JavaScript confirmation dialog muncul
   ↓
3. Jika confirm "OK":
   - Redirect ke proses_user.php?action=delete&id=[id]
   - Validasi proteksi self-delete
   - DELETE dari database
   - Redirect ke kelola_user.php?success=delete
   ↓
4. Jika confirm "Cancel":
   - Tidak ada aksi
```

---

## ⚠️ Error Handling

### Kemungkinan Error Messages

| Error | Penyebab | Solusi |
|-------|----------|--------|
| "Semua field wajib diisi!" | Ada field required kosong | Isi semua field yang wajib |
| "Username sudah digunakan!" | Username duplikat saat add | Gunakan username lain |
| "Username sudah digunakan oleh user lain!" | Username duplikat saat edit | Gunakan username lain |
| "Data tidak lengkap!" | ID atau field required kosong | Periksa form data |
| "ID tidak valid!" | ID user tidak ditemukan | Periksa parameter URL |
| "Tidak dapat menghapus akun sendiri!" | Admin mencoba hapus diri sendiri | Login dengan admin lain |
| "Aksi tidak valid!" | Parameter action salah | Gunakan: add, edit, atau delete |

---

## 🔄 Dependencies

### PHP Libraries
- **PHP >= 7.4**: Untuk password_hash dan modern syntax
- **PDO Extension**: Database connection
- **Session Extension**: User authentication

### Frontend Libraries
- **Bootstrap 5.3.3**: UI framework
- **Bootstrap Icons**: Icon library
- **JavaScript ES6+**: Modern browser required

### Database
- **MySQL 8.0+**: Database server
- **InnoDB Engine**: Transaction support

---

## 📊 Database Query Reference

### Select All Users (Ordered by Newest)
```sql
SELECT * FROM users ORDER BY created_at DESC;
```

### Check Username Exists
```sql
SELECT id FROM users WHERE username = ?;
```

### Insert New User
```sql
INSERT INTO users (nama, username, password, role, prodi) 
VALUES (?, ?, ?, ?, ?);
```

### Update User (With Password)
```sql
UPDATE users 
SET nama = ?, username = ?, password = ?, role = ?, prodi = ? 
WHERE id = ?;
```

### Update User (Without Password)
```sql
UPDATE users 
SET nama = ?, username = ?, role = ?, prodi = ? 
WHERE id = ?;
```

### Delete User
```sql
DELETE FROM users WHERE id = ?;
```

---

## 🧪 Testing Checklist

### ✅ Add User Testing
- [ ] Tambah user dengan semua field terisi
- [ ] Tambah user mahasiswa (prodi field muncul)
- [ ] Tambah user admin (prodi field hidden)
- [ ] Coba username duplikat (harus error)
- [ ] Coba field kosong (harus error)
- [ ] Cek password di-hash dengan benar di database

### ✅ Edit User Testing
- [ ] Edit nama user
- [ ] Edit username ke username baru
- [ ] Edit username ke username user lain (harus error)
- [ ] Edit dengan password baru (password berubah)
- [ ] Edit tanpa password (password tetap)
- [ ] Edit role dari mahasiswa ke admin (prodi jadi null)
- [ ] Edit role dari admin ke mahasiswa (isi prodi)

### ✅ Delete User Testing
- [ ] Hapus user biasa (berhasil)
- [ ] Coba hapus akun sendiri (harus error)
- [ ] Konfirmasi hapus (OK vs Cancel)

### ✅ UI/UX Testing
- [ ] Search bar berfungsi real-time
- [ ] Modal fade in/out smooth
- [ ] Alert auto-dismiss setelah 5 detik
- [ ] Form reset setelah modal ditutup
- [ ] Responsive di mobile, tablet, desktop
- [ ] Avatar circle show initial nama
- [ ] Badge role show warna yang benar

---

## 📈 Future Enhancements

### Potential Improvements
1. **Pagination**: Limit data per halaman (10, 25, 50)
2. **Export**: Export user list ke Excel/PDF
3. **Filter**: Filter by role (Admin/Mahasiswa), Prodi
4. **Sorting**: Sortable columns (klik header untuk sort)
5. **Bulk Actions**: Select & delete multiple users
6. **User Profile**: Upload foto profil user
7. **Email Notification**: Email saat user dibuat
8. **Activity Log**: Track who create/edit/delete user
9. **Password Strength**: Real-time password strength meter
10. **Two-Factor Auth**: 2FA untuk admin

---

## 👨‍💻 Developer Notes

### Coding Standards
- **PSR-12**: PHP coding style
- **Indentation**: 4 spaces
- **Comments**: Bahasa Indonesia
- **Variables**: camelCase
- **Functions**: camelCase
- **Constants**: UPPER_CASE

### Best Practices Applied
✅ Separation of Concerns (View vs Logic)  
✅ DRY (Don't Repeat Yourself)  
✅ SOLID Principles  
✅ Secure by Default  
✅ Progressive Enhancement  
✅ Mobile First Design  

---

## 📞 Support & Contact

**Developer**: Muhammad Adha  
**Project**: Sistem Peminjaman Ruangan  
**Module**: Kelola User  
**Version**: 1.0.0  
**Last Updated**: 19 Februari 2026

---

## 📄 License

Proyek ini dikembangkan untuk keperluan akademik.

---

## 🎓 Credits

- **Bootstrap 5**: https://getbootstrap.com
- **Bootstrap Icons**: https://icons.getbootstrap.com
- **PHP Documentation**: https://www.php.net
- **MDN Web Docs**: https://developer.mozilla.org

---

**© 2026 Muhammad Adha - Sistem Peminjaman Ruangan**

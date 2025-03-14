<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Validator;


// class UserController extends Controller implements HasMiddleware
class UserController extends Controller 
{
    // public static function middleware(): array
    // {
    //     return [
    //         new Middleware('role:super-admin'),
    //     ];
    // }
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::select('*');
            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('user_name', function ($user) {
                    return $user->name ? $user->name : 'N/A';
                })
                ->addColumn('email', function ($user) {
                    return $user->email ? $user->email : 'N/A';
                })
                ->addColumn('whatsapp', function ($user) {
                    return $user->whatsapp ? $user->whatsapp : 'N/A';
                })
                ->addColumn('roles', function ($user) {
                    // Menampilkan nama role
                    if ($user->roles && $user->roles->isNotEmpty()) {
                        return $user->roles->pluck('name')->join(', ');
                    }
                    return 'N/A'; // Jika tidak ada role
                })
                ->addColumn('options', function ($user) {
                    return '
                        <button class="btn btn-primary btn-sm" onclick="showEditModal(' . $user->id . ')">Edit</button>
                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(' . $user->id . ')">Delete</button>
                    ';
                })
                ->rawColumns(['options'])  // Pastikan menambahkan ini untuk kolom options
                ->make(true);
        }

        // Ambil data roles untuk dikirim ke view
        $roles = Role::select('id', 'name')->get();
        return view('backend.users.index',  compact('roles'));
    }

    // Method untuk menyimpan data user baru
    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'whatsapp' => 'required|string|unique:users|max:15',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()]);
        }


        // Menyimpan data ke tabel users
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp' => $request->whatsapp,
            'password' => bcrypt($request->name), // Set password default atau sesuai logika Anda
        ]);

        // Menambahkan role ke user
        $role = Role::find($request->role_id);
        $user->assignRole($role);


        return response()->json(['success' => true]);
    }

    public function getAdmin($id)
    {
        try {
            $user = User::with('roles:id,name')->findOrFail($id);

            return response()->json(['success' => true, 'data' => $user]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            //Validasi untuk 'name'
            $request->validate([
                'name' => 'required|string|max:255',
            ]);

            $user = User::findOrFail($id);

            if ($user->email != $request->email) {
                // Validasi untuk 'email'
                $request->validate([
                    'email' => 'required|email|max:255|unique:users,email,' . $id, // Pastikan email unik kecuali untuk user ini
                ]);
            }


            if ($user->whatsapp != $request->whatsapp) {
                // Validasi untuk 'wa'
                $request->validate([
                    'whatsapp' => 'required|string|unique:users|max:15', // Sesuaikan dengan format whatsapp
                ]);
            }

            // Validasi untuk 'role_id'
            $request->validate([
                'role_id' => 'required|exists:roles,id', // Pastikan role ada di tabel roles
            ]);


            // Cari admin berdasarkan ID
            // $admin = UserAdmin::findOrFail($id);

            // Perbarui data user terkait (user yang memiliki ID user_id di UserAdmin)
            $user = $admin->user;  // Ambil user yang terkait dengan admin ini
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp' => $request->whatsapp,
            ]);

            // Perbarui role untuk user terkait
            $user->roles()->sync([$request->role_id]);  // Sinkronkan role baru dengan user

            return response()->json(['success' => true, 'message' => 'update data berhasil.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function softdelete($id)
    {
        try {
            // Cari admin berdasarkan ID
            $user = User::findOrFail($id);

            // Set is_deleted = 1 untuk soft delete admin

            if ($user) {
                // Set is_deleted = 1 untuk soft delete user
                $user->is_deleted = 1;
                $user->save();  // Simpan perubahan
                $user->delete();
            }

            return response()->json(['success' => true, 'message' => 'Hapus data berhasil']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
}

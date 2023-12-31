<?php

namespace App\Http\Controllers\admin;

use Throwable;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\User\StoreRequest;
use App\Http\Controllers\Trait\TitleTrait;
use App\Http\Controllers\Trait\ResponseTrait;
use App\Http\Requests\User\StoreOwnerRequest;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    use ResponseTrait;
    use TitleTrait;
    private object $model;
    private string $table;

    public function __construct()
    {
        $this->model = User::query();
        $this->table = (new User())->getTable();

        $routerName = Route::currentRouteName();

        View::share('title', ucfirst($this->getTitleRoute($routerName)));
    }

    public function index()
    {
        // check authorization super admin
        $this->authorize('index', User::class);

        $arrRole = UserRoleEnum::getArrayView();
        array_shift($arrRole); // remove super admin role

        return view('admin.user.index', [
            'arrRole' => $arrRole,
        ]);
    }

    public function api()
    {
        // check authorization super admin
        $this->authorize('index', User::class);

        return Datatables::of($this->model)
            ->editColumn('role', function ($object) {
                return UserRoleEnum::getKeyByValue($object->role);
            })
            // ->addColumn('edit', function ($object) {
            //     return route('admin.users.edit', $object);
            // })
            ->addColumn('destroy', function ($object) {
                return route('admin.users.destroy', $object);
            })
            ->filterColumn('role', function ($query, $keyword) {
                if ($keyword !== '-1') {
                    $query->where('role', $keyword);
                }
            })
            ->make(true);
    }

    public function apiName(Request $request)
    {
        // check authorization super admin
        $this->authorize('index', User::class);

        return $this->model
            ->where('name', 'like', '%' . $request->get('q') . '%')
            ->get([
                'id',
                'name',
            ]);
    }

    public function create()
    {
        // check authorization super admin
        $this->authorize('index', User::class);

        $arrRole = UserRoleEnum::getArrayView();
        array_shift($arrRole); // remove super admin role

        return view('admin.user.create', [
            'arrRole' => $arrRole,
        ]);
    }

    public function store(StoreRequest $request)
    {
        // check authorization super admin
        $this->authorize('index', User::class);

        try {

            $arr = $request->validated();

            if ($request->hasFile('avatar')) {
                $destination_path = 'public/images/user_avatar/' . $request->phone;
                $image = $request->file('avatar');
                $image_name = $image->getClientOriginalName();
                $path = $request->file('avatar')->storeAs($destination_path, $image_name);

                $getHost = request()->getHost();
                $arr['avatar'] =  $getHost . '/storage/images/user_avatar/' . $request->phone . '/' . $image_name;
            }

            $user = User::create($arr);

            return $this->successResponse();
        } catch (Throwable $e) {
            $message = '';
            if ($e->getCode() === '23000') {
                $message = 'Duplicate phone or email';
            }
            return $this->errorResponse($message);
        }
    }

    public function destroy($userId)
    {
         // check authorization super admin
         $this->authorize('index', User::class);

        $userPhone = User::find($userId)->phone;

        $this->model->find($userId)->delete();
        Storage::deleteDirectory(public_path('images/user_avatar' . $userPhone));
    }

    public function getOwner(Request $request)
    {
        // check authorization super admin and admin
        $this->authorize('index2', User::class);

        $data = $this->model
            ->where([
                ['name', 'like', '%' . $request->get('q') . '%'],
                ['role', 2],
            ],)
            ->get();

        return $this->successResponse($data);
    }

    public function checkOwner($ownerName)
    {
        // check authorization super admin and admin
        $this->authorize('index2', User::class);


        $check = $this->model
            ->where([
                ['name', '=', $ownerName],
                ['role', '=', UserRoleEnum::OWNER],
            ])
            ->exists();
        return $this->successResponse($check);
    }

    public function storeOwner(StoreOwnerRequest $request)
    {
        // check authorization super admin and admin
        $this->authorize('index2', User::class);

        try {
            $arr = $request->validated();
            $arr['password'] = Hash::make('1'); // default when creating by admin or super admin
            $arr['role'] = UserRoleEnum::OWNER;
            User::create($arr);

            return $this->successResponse();
        } catch (Throwable $e) {
            $message = '';
            if ($e->getCode() === '23000') {
                $message = 'Duplicate phone or email';
            }
            return $this->errorResponse($message);
        }
    }
}

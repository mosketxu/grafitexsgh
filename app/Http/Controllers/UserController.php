<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Caffeinated\Shinobi\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     * 
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->busca) {
            $busqueda = $request->busca;
        } else {
            $busqueda = '';
        } 

        $users=User::search($request->busca)
	->with('roles')
        ->paginate();
        
        return view('users.index',compact('users','busqueda'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'=>'required',
            'email' => 'required|email:rfc|unique:users,email',
            'password'=>'required',
            ]);
            
        DB::table('users')->insert([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>bcrypt($request->password),
            'created_at'=>now(),
            'updated_at'=>now(),
             ]
        );

        $notification = array(
            'message' => 'User creado satisfactoriamente!',
            'alert-type' => 'success'
        );
        return redirect('user')->with($notification);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $roles = auth()->user()->roles;

        return view('users.edit', compact('user','roles'));

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user)
    {
        $roles=Role::get(); 

        return view('users.edit', compact('user','roles'));
    }

    public function tempo(Request $request)
    {
        $user=User::where('id','>','7')
        ->get();

        foreach ($user as $us) {
            $us->roles()->sync('12');
        }
        // dd($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $data = request()->validate([
            'name' => 'required',
            // 'email' => 'required|email|unique:users,email,'.$user->id,
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => '',
            ]);
            
            if ($data['password'] != null) {
                $data['password'] = bcrypt($data['password']);
            } else {
                unset($data['password']);
            }
            
            $user->update($data);
            $user->roles()->sync($request->get('roles'));
            
            $notification = array(
                'message' => '¡Usuario actualizado satisfactoriamente!',
                'alert-type' => 'success'
            );
        return redirect()->back()->with($notification);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try{
            User::destroy($id);;   
        }catch(\ErrorException $ex){
            return back()->withError($ex->getMessage());
        } 
   
        $notification = array(
            'message' => 'User eliminado satisfactoriamente!',
            'alert-type' => 'success'
        );
    
        return response()->json([
            'id'=>$id,
            'notificacion'=>$notification,
        ]);
    }
}

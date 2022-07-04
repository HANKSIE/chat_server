<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\Group;

class GroupController extends Controller
{
    public function index()
    {
        return auth()->user()->groups()->select('groups.id')->get()->map(function($group)
        {
            return $group->id;
        });
    }

    public function store($request)
    {
        //
    }

    public function show(Group $group)
    {
        //
    }

    public function update($request, Group $group)
    {
        //
    }

    public function destroy(Group $group)
    {
        //
    }
}

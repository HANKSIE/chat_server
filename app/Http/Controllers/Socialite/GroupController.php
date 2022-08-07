<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Response;

class GroupController extends Controller
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function recentContact($isOneToOne, $perPage = 5)
    {
        return $this->groupService->recentContactCursorPaginate(auth()->user()->id, $isOneToOne, $perPage);
    }

    public function index()
    {
        return response()->json(['groups' => $this->groupService->getAllIDs(auth()->user()->id)]);
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

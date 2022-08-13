<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    private $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function recentContactPaginate(Request $request)
    {
        $isOneToOne = $request->query('is_one_to_one');
        $perPage = $request->query('per_page');
        return $this->groupService->recentContactPaginate(auth()->user()->id, $isOneToOne, $perPage);
    }

    public function messageReads($groupID)
    {
        return response()->json(['message_reads' => $this->groupService->messageReads($groupID)]);
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

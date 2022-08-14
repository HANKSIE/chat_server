<?php

namespace App\Http\Controllers\Socialite;

use App\Http\Controllers\Controller;
use App\Repositories\GroupRepository;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    private $groupRepository;

    public function __construct(GroupRepository $groupRepository)
    {
        $this->groupRepository = $groupRepository;
    }

    public function recentContactPaginate(Request $request)
    {
        $isOneToOne = $request->query('is_one_to_one');
        $perPage = $request->query('per_page');
        return $this->groupRepository->recentContactPaginate(auth()->user()->id, $isOneToOne, $perPage);
    }

    public function messageReads($groupID)
    {
        return response()->json(['message_reads' => $this->groupRepository->getMessageReads($groupID)]);
    }

    public function index()
    {
        return response()->json(['groups' => $this->groupRepository->getAllIDs(auth()->user()->id)]);
    }
}

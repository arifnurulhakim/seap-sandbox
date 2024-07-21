<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends Controller
{
    public function index()
    {
        try {
            $offset = request()->get('offset', 0);
            $limit = request()->get('limit', 10);
            $search = request()->get('search', '');
            $getOrder = request()->get('order', '');
            $defaultOrder = $getOrder ? $getOrder : "id ASC";
            $orderMappings = [
                'idASC' => 'id ASC',
                'idDESC' => 'id DESC',
                'nameProspectASC' => 'name_prospect ASC',
                'nameProspectDESC' => 'name_prospect DESC',
                'channelASC' => 'channel ASC',
                'channelDESC' => 'channel DESC',
                'contactASC' => 'contact ASC',
                'contactDESC' => 'contact DESC',
                'picASC' => 'pic ASC',
                'picDESC' => 'pic DESC',
                'typeProjectASC' => 'type_project_id ASC',
                'typeProjectDESC' => 'type_project_id DESC',
                'typeProspectASC' => 'type_prospect_id ASC',
                'typeProspectDESC' => 'type_prospect_id DESC',
                'typeIndustryASC' => 'type_industry_id ASC',
                'typeIndustryDESC' => 'type_industry_id DESC',
                'lastContactASC' => 'last_contact ASC',
                'lastContactDESC' => 'last_contact DESC',
                'detailsASC' => 'details ASC',
                'detailsDESC' => 'details DESC',
                'statusASC' => 'status ASC',
                'statusDESC' => 'status DESC',
                'declineReasonASC' => 'decline_reason ASC',
                'declineReasonDESC' => 'decline_reason DESC',
                'createdByASC' => 'created_by ASC',
                'createdByDESC' => 'created_by DESC',
                'modifiedByASC' => 'modified_by ASC',
                'modifiedByDESC' => 'modified_by DESC',
            ];

            // Set the order based on the mapping or use the default order if not found
            $order = $orderMappings[$getOrder] ?? $defaultOrder;
            // Validation rules for input parameters
            $validOrderValues = implode(',', array_keys($orderMappings));
            $rules = [
                'offset' => 'integer|min:0',
                'limit' => 'integer|min:1',
                'order' => "in:$validOrderValues",
            ];

            $validator = Validator::make([
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
            ], $rules);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'ERROR',
                    'message' => 'Invalid input parameters',
                    'errors' => $validator->errors(),
                ], 400);
            }
            $contactsQuery = Contact::select(
                'contacts.id',
                'contacts.name_prospect',
                'contacts.channel',
                'contacts.contact',
                'contacts.last_contact',
                DB::raw('DATE_FORMAT(pis_contacts.last_contact, "%d %b %Y") as last_contact_string'),
                'contacts.details',
                'contacts.status',
                'contacts.decline_reason',
                'contacts.type_project_id',
                'contacts.type_prospect_id',
                'contacts.type_industry_id',
                'contacts.pic as pic_id',
                'type_projects.name as project_name',
                'type_prospects.name as prospect_name',
                'type_industries.name as industry_name',
                'pic_user.name as pic_name',
                'created_user.name as created_by_name',
                'modified_user.name as modified_by_name',
                'contacts.created_at',
                'contacts.updated_at'
            )
                ->leftJoin('type_projects', 'contacts.type_project_id', '=', 'type_projects.id')
                ->leftJoin('type_prospects', 'contacts.type_prospect_id', '=', 'type_prospects.id')
                ->leftJoin('type_industries', 'contacts.type_industry_id', '=', 'type_industries.id')
                ->leftJoin('users as pic_user', 'contacts.pic', '=', 'pic_user.id')
                ->leftJoin('users as created_user', 'contacts.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'contacts.modified_by', '=', 'modified_user.id')
                ->orderByRaw($order)
                ->offset($offset)
                ->limit($limit);

            if ($search !== '') {
                $contactsQuery->where(function ($query) use ($search) {
                    $query->where('contacts.name_prospect', 'like', "%$search%")
                        ->orWhere('contacts.channel', 'like', "%$search%")
                        ->orWhere('contacts.contact', 'like', "%$search%")
                        ->orWhere('contacts.last_contact', 'like', "%$search%")
                        ->orWhere(DB::raw('DATE_FORMAT(pis_contacts.last_contact, "%d %b %Y")'), 'like', "%$search%")
                        ->orWhere('contacts.details', 'like', "%$search%")
                        ->orWhere('contacts.status', 'like', "%$search%")
                        ->orWhere('contacts.decline_reason', 'like', "%$search%")
                        ->orWhere('type_projects.name', 'like', "%$search%")
                        ->orWhere('type_prospects.name', 'like', "%$search%")
                        ->orWhere('type_industries.name', 'like', "%$search%")
                        ->orWhere('pic_user.name', 'like', "%$search%")
                        ->orWhere('created_user.name', 'like', "%$search%")
                        ->orWhere('modified_user.name', 'like', "%$search%");
                });
            }

            $contacts = $contactsQuery->get();
            return response()->json([
                'status' => 'SUCCESS',
                'offset' => $offset,
                'limit' => $limit,
                'order' => $getOrder,
                'search' => $search,
                'data' => $contacts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $contact = Contact::select(
                'contacts.id',
                'contacts.name_prospect',
                'contacts.channel',
                'contacts.contact',
                'contacts.last_contact',
                DB::raw('DATE_FORMAT(pis_contacts.last_contact, "%d %b %Y") as last_contact_string'),
                'contacts.details',
                'contacts.status',
                'contacts.decline_reason',
                'contacts.type_project_id',
                'contacts.type_prospect_id',
                'contacts.type_industry_id',
                'contacts.pic as pic_id',
                'type_projects.name as project_name',
                'type_prospects.name as prospect_name',
                'type_industries.name as industry_name',
                'pic_user.name as pic_name',
                'created_user.name as created_by_name',
                'modified_user.name as modified_by_name',
                'contacts.created_at', 'contacts.updated_at', )
                ->leftJoin('type_projects', 'contacts.type_project_id', '=', 'type_projects.id')
                ->leftJoin('type_prospects', 'contacts.type_prospect_id', '=', 'type_prospects.id')
                ->leftJoin('type_industries', 'contacts.type_industry_id', '=', 'type_industries.id')
                ->leftJoin('users as pic_user', 'contacts.pic', '=', 'pic_user.id')
                ->leftJoin('users as created_user', 'contacts.created_by', '=', 'created_user.id')
                ->leftJoin('users as modified_user', 'contacts.modified_by', '=', 'modified_user.id')
                ->where('id', $id)->first();
            if (!isset($contact)) {
                return response()->json(['status' => 'ERROR', 'message' => 'NOT FOUND'], 404);
            }
            return response()->json(['status' => 'SUCCESS', 'data' => $contact], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name_prospect' => 'required|string|max:255',
                'channel' => 'required|string|max:125',
                'contact' => 'required|string',
                'pic' => 'integer|exists:users,id',
                'type_project_id' => 'integer|exists:type_projects,id',
                'type_prospect_id' => 'integer|exists:type_prospects,id',
                'type_industry_id' => 'integer|exists:type_industries,id',
                'last_contact' => 'date',
                'details' => 'string',
                'status' => 'string|in:Proposal Sent,Goal,Pitching,Declined',
                'decline_reason' => 'string|max:255',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $contact = Contact::create([
                'name_prospect' => $request->input('name_prospect'),
                'channel' => $request->input('channel'),
                'contact' => $request->input('contact'),
                'pic' => $request->input('pic'),
                'type_project_id' => $request->input('type_project_id'),
                'type_prospect_id' => $request->input('type_prospect_id'),
                'type_industry_id' => $request->input('type_industry_id'),
                'last_contact' => $request->input('last_contact'),
                'details' => $request->input('details'),
                'status' => $request->input('status') ?? 'undefined',
                'decline_reason' => $request->input('decline_reason'),
                'created_by' => Auth::user()->id,
                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Contact created successfully', 'data' => $contact], 201);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $contact = Contact::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name_prospect' => 'string|max:255',
                'channel' => 'string|max:125',
                'contact' => 'string',
                'pic' => 'integer|exists:users,id',
                'type_project_id' => 'integer|exists:type_projects,id',
                'type_prospect_id' => 'integer|exists:type_prospects,id',
                'type_industry_id' => 'integer|exists:type_industries,id',
                'last_contact' => 'date',
                'details' => 'string',
                'status' => 'string|in:Proposal Sent,Goal,Pitching,Declined',
                'decline_reason' => 'string|max:255',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            // $picUrl = $contact->pic;

            // if ($request->hasFile('pic')) {
            //     $pic = $request->file('pic');
            //     $picName = Str::random(12) . '-' . $pic->getClientOriginalName();
            //     $pic->move(public_path('contact_pics/'), $picName);
            //     $picUrl = 'contact_pics/' . $picName;

            //     // Hapus gambar lama jika ada
            //     if ($contact->pic) {
            //         File::delete(public_path($contact->pic));
            //     }
            // }

            $contact->update([
                'name_prospect' => $request->filled('name_prospect') ? $request->input('name_prospect') : $contact->name_prospect,
                'channel' => $request->filled('channel') ? $request->input('channel') : $contact->channel,
                'contact' => $request->filled('contact') ? $request->input('contact') : $contact->contact,
                'pic' => $request->input('pic'),
                'type_project_id' => $request->filled('type_project_id') ? $request->input('type_project_id') : $contact->type_project_id,
                'type_prospect_id' => $request->filled('type_prospect_id') ? $request->input('type_prospect_id') : $contact->type_prospect_id,
                'type_industry_id' => $request->filled('type_industry_id') ? $request->input('type_industry_id') : $contact->type_industry_id,
                'last_contact' => $request->filled('last_contact') ? $request->input('last_contact') : $contact->last_contact,
                'details' => $request->filled('details') ? $request->input('details') : $contact->details,
                'status' => $request->filled('status') ? $request->input('status') : $contact->status,
                'decline_reason' => $request->filled('decline_reason') ? $request->input('decline_reason') : $contact->decline_reason,
                'created_by' => $request->filled('created_by') ? $request->input('created_by') : $contact->created_by,
                'modified_by' => Auth::user()->id,
            ]);

            return response()->json(['status' => 'success', 'message' => 'Contact updated successfully', 'data' => $contact], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Something went wrong'], 500);
        }
    }
    public function destroy($id)
    {
        try {
            $contact = Contact::findOrFail($id);
            $contact->delete();
            return response()->json(['status' => 'SUCCESS', 'message' => 'Contact deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => 'ERROR', 'message' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

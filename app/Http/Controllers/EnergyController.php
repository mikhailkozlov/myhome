<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnergyRequest;
use Illuminate\Http\Request;
use App\Energy;
use App\Http\Requests;
use App\Http\Controllers\Controller;

class EnergyController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        return response()->json(Energy::orderBy('created_at', 'DESC')->paginate(20));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
	$this->validate($request, [
		'sensor_id' => 'required|numeric',
        	'node'      => 'required|numeric',
	        'instance'  => 'required|numeric|size:1',
        	'value'     => 'required|numeric|min:1'
	]);
        //
        $meter = new Energy($request->input());

        $meter->save();

        return response()->json($meter);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meter = Energy::findOrFail($id);

        return response()->json($meter);

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $meter = Energy::findOrFail($id);
        $meter->save($request->input());

        return response()->json($meter);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meter = Energy::findOrFail($id);
        $meter->delete();

        return response()->json(['status' => "ok"]);
    }
}

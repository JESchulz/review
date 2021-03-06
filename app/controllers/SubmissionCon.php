<?php

class SubmissionCon extends \BaseController {

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
        $submissions = Submission::all();
        return View::make('submission.index')->withSubmissions($submissions);
	}


	/**
	 * Show the form for creating a new resource.
	 *
	 * @return Response
	 */
	public function create($category_id, $user_id = null)
	{
        if ($user_id === null)
        {
            $user_id = Auth::user()->id;
        }

        $action = array('route' => array('submission.store'));
        $user = User::find($user_id);
        $category = Category::find($category_id);

        return View::make('submission.create')
            ->withAction($action)
            ->withUser($user)
            ->withCategory($category);
	}


	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
    public function store()
    {
        $rules = array(
            'title' => 'required|min:1',
            'user_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'document' => 'required|max:10000000|min:1|mimes:pdf,doc,docx',
        );

        $validator = Validator::make(Input::all(), $rules);

        if ($validator->fails())
        {
            Input::flash();
            return Redirect::route('category.submission',
                array(Input::get('category_id'), Input::get('user_id')))
                    ->withErrors($validator);
        }

        $submission = new Submission(Input::all());
        $submission->user_id = Input::get('user_id');
        $submission->category_id = Input::get('category_id');

        $keywords = Keyword::whereIn('id', Input::get('keywords'))->get();

        $kws = array();
        foreach($keywords as $kw) {
            $kws[] = $kw;
        }

        $file = Input::file('document');
        $document = new Document;
        $document->author_can_read = true;
        $document->user_id = Input::get('user_id');

        // Sanitize
        $name = $file->getClientOriginalName();
        $name = pathinfo($name)['filename'];
        $name = trim($name);
        $name = preg_replace('/[^a-zA-Z0-9]+/', '_', $name);
        $name = ltrim($name, '_');
        $name = "$name." . $file->getClientOriginalExtension();
        $document->name = $name;
        $document->saved_name = uniqid() . '/' . $document->name;

        $file->move('uploads/'.dirname($document->saved_name), basename($document->saved_name));

        $submission->save();
        $submission->keywords()->saveMany($kws);
        $document->container()->associate($submission);
        $document->save();

        return Redirect::route('submission.show',
            array('submission_id' => $submission->id)
        );
    }


	/**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($submission_id)
	{
        $submission = Submission::find($submission_id);
        return View::make('submission.show')->withSubmission($submission);
	}


	/**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function edit($id)
	{
        //
	}


	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		//
	}


	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		//
	}


}

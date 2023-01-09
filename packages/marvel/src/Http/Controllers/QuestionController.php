<?php


namespace Marvel\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Marvel\Database\Models\Question;
use Marvel\Database\Models\Settings;
use Marvel\Database\Repositories\QuestionRepository;
use Marvel\Exceptions\MarvelException;
use Marvel\Http\Requests\QuestionCreateRequest;
use Marvel\Http\Requests\QuestionUpdateRequest;
use Prettus\Validator\Exceptions\ValidatorException;


class QuestionController extends CoreController
{
    public $repository;

    public function __construct(QuestionRepository $repository)
    {
        $this->repository = $repository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Collection|Question[]
     */
    public function index(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;
        $productId = $request['product_id'];

        if (isset($productId) && !empty($productId)) {
            if (null !== $request->user()) {
                $request->user()->id;
            }
            return $this->repository->where([
                ['product_id', '=', $productId],
                ['answer', '!=', null]
            ])->paginate($limit);
        }
        if (isset($request['answer']) && $request['answer'] === 'null') {
            return $this->repository->paginate($limit);
        }
        return $this->repository->where('answer', '!=', null)->paginate($limit);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  QuestionCreateRequest  $request
     * @return mixed
     * @throws Exception
     */
    public function store(QuestionCreateRequest $request): Question
    {
        try {
            $productQuestionCount = $this->repository->where([
                'product_id' => $request['product_id'],
                'user_id'    => $request->user()->id,
                'shop_id'    => $request['shop_id']
            ])->count();

            $settings = Settings::getData();
            $maximumQuestionLimit = isset($settings['options']['maximumQuestionLimit']) ? $settings['options']['maximumQuestionLimit'] : 5;

            if ($maximumQuestionLimit <= $productQuestionCount) {
                abort(400);
            }

            return $this->repository->storeQuestion($request);
        } catch (Exception $e) {
            throw new Exception(MAXIMUM_QUESTION_LIMIT_EXCEEDED);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return JsonResponse
     */
    public function show($id)
    {
        try {
            return $this->repository->findOrFail($id);
        } catch (\Exception $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    public function update(QuestionUpdateRequest $request, $id)
    {
        $request->id = $id;
        return $this->updateQuestion($request, $id);
    }

    public function updateQuestion(Request $request)
    {
        if ($this->repository->hasPermission($request->user(), $request->shop_id)) {
            $id = $request->id;
            return $this->repository->updateQuestion($request, $id);
        } else {
            throw new MarvelException(NOT_AUTHORIZED);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy($id)
    {
        try {
            return $this->repository->findOrFail($id)->delete();
        } catch (\Exception $e) {
            throw new MarvelException(NOT_FOUND);
        }
    }

    /**
     * Display a listing of the resource for authenticated user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function myQuestions(Request $request)
    {
        $limit = $request->limit ? $request->limit : 15;

        return $this->repository->where('user_id', auth()->user()->id)->with('product')->paginate($limit);
    }
}

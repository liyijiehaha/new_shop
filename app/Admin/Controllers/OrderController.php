<?php

namespace App\Admin\Controllers;

use App\Model\OrderModel;
use App\Http\Controllers\Controller;
use Encore\Admin\Controllers\HasResourceActions;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Layout\Content;
use Encore\Admin\Show;

class OrderController extends Controller
{
    use HasResourceActions;

    /**
     * Index interface.
     *
     * @param Content $content
     * @return Content
     */
    public function index(Content $content)
    {
        return $content
            ->header('Index')
            ->description('description')
            ->body($this->grid());
    }

    /**
     * Show interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('Detail')
            ->description('description')
            ->body($this->detail($id));
    }

    /**
     * Edit interface.
     *
     * @param mixed $id
     * @param Content $content
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content
            ->header('Edit')
            ->description('description')
            ->body($this->form()->edit($id));
    }

    /**
     * Create interface.
     *
     * @param Content $content
     * @return Content
     */
    public function create(Content $content)
    {
        return $content
            ->header('Create')
            ->description('description')
            ->body($this->form());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new OrderModel);

        $grid->order_id('Order id');
        $grid->order_amount('Order amount');
        $grid->user_id('User id');
        $grid->create_time('Create time');
        $grid->pay_time('Pay time');
        $grid->status('Status');
        $grid->order_sn('Order sn');
        $grid->is_detele('Is detele');

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function detail($id)
    {
        $show = new Show(OrderModel::findOrFail($id));

        $show->order_id('Order id');
        $show->order_amount('Order amount');
        $show->user_id('User id');
        $show->create_time('Create time');
        $show->pay_time('Pay time');
        $show->status('Status');
        $show->order_sn('Order sn');
        $show->is_detele('Is detele');

        return $show;
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new OrderModel);

        $form->number('order_id', 'Order id');
        $form->text('order_amount', 'Order amount');
        $form->number('user_id', 'User id');
        $form->number('create_time', 'Create time');
        $form->number('pay_time', 'Pay time');
        $form->switch('status', 'Status')->default(1);
        $form->text('order_sn', 'Order sn');
        $form->switch('is_detele', 'Is detele');

        return $form;
    }
}

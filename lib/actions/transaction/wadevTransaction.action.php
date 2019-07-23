<?php

class wadevTransactionAction extends wadevViewAction
{
    public function execute()
    {
        $t = new wadevTransaction(new wadevTransactionModel());
        $new_transactions_count = $t->updateFromApi();
        $last_update = (int)wa('wadev')->getConfig()->getSetting('api.transactions');

        // удалим инфу о новых
        (new waAppSettingsModel())->set('wadev', 'new_transactions', 0);
        $counts = wa()->getStorage()->get('apps-count');
        $counts['wadev'] = 0;
        wa()->getStorage()->write('apps-count', $counts);

        $search = waRequest::get('search', '', waRequest::TYPE_STRING_TRIM);
        $start = waRequest::param('start', 0, waRequest::TYPE_INT);
        $limit = waRequest::param('limit', 10, waRequest::TYPE_INT);
        $from = waRequest::get('from', '', waRequest::TYPE_STRING_TRIM);
        $to = waRequest::get('to', '', waRequest::TYPE_STRING_TRIM);
        $total_rows = true;

        /** @var wadevTransactionModel[] $transactions */
        $transactions = wadevTransactionModel::model()->findAll(
            $search,
            [strtotime($from), strtotime($to)],
            [$start, $limit],
            $total_rows
        );

        $total = ['plus' => 0, 'minus' => 0];
        if ($transactions){
        foreach ($transactions as $transaction) {
            $total[$transaction->amount > 0 ? 'plus' : 'minus'] += $transaction->amount;
        }
        }
        $balance = wa('wadev')->getConfig()->currentBalance(true);

        wadevHelper::assignPagination($this->view, $start, $limit, $total_rows);

        $this->view->assign(compact('search', 'from', 'to'));
        $this->view->assign(compact('balance', 'new_transactions_count', 'transactions', 'total', 'last_update'));
    }
}

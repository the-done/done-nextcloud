<?php

/**
 * SPDX-FileCopyrightText: 2025 The Done contributors
 * SPDX-License-Identifier: MIT
 */

declare(strict_types=1);

namespace OCA\Done\Demo\Providers;

use OCA\Done\Demo\AbstractDemoProvider;
use OCP\IRequest;

/**
 * Fake, read-only demo data for the Finances module. English only.
 * Does not reference any OCA\Done\Modules\Finances class - every shape below
 * is hardcoded to mirror the real FinancesController / TableService responses.
 */
class FinancesDemoProvider extends AbstractDemoProvider
{
    /** @var string Demo customer used across the fake dataset. */
    private const CUSTOMER = 'Northwind Ltd';

    /** @var string Demo contract id used by the contract read endpoints. */
    private const CONTRACT_ID = 'demo-con-1';

    protected function readMethods(): array
    {
        return [
            // DynamicTable sources (mirror TableService::getTableDataForEntity).
            'getPaymentsTableData'                => fn (IRequest $r): array => $this->paymentsTable(),
            'getContractsTableData'               => fn (IRequest $r): array => $this->contractsTable(),
            'getContractsParametersTableData'     => fn (IRequest $r): array => $this->contractParametersTable(),
            'getContractParameterGroupsTableData' => fn (IRequest $r): array => $this->contractParameterGroupsTable(),

            // Payment read endpoints (mirror FinancesController).
            'getPayments'     => fn (IRequest $r): array => $this->payments(),
            'getPayment'      => fn (IRequest $r): array => $this->paymentCard(),
            'getPaymentTypes' => fn (IRequest $r): array => $this->paymentTypes(),
            'getSimpleUsers'  => fn (IRequest $r): array => $this->simpleUsers(),
            'getCustomers'    => fn (IRequest $r): array => $this->customers(),

            // Contract read endpoints.
            'getContracts'    => fn (IRequest $r): array => $this->contracts(),
            'getContractData' => fn (IRequest $r): array => $this->contractData(),

            // Contract-parameter read endpoints.
            'getContractParameter'       => fn (IRequest $r): array => $this->contractParameterCard(),
            'getContractParameters'      => fn (IRequest $r): array => $this->contractParameters(),
            'getContractParameterValues' => fn (IRequest $r): array => $this->contractParameterValues(),

            // Contract-parameter-group read endpoints.
            'getContractParameterGroup'  => fn (IRequest $r): array => $this->contractParameterGroupCard(),
            'getContractParameterGroups' => fn (IRequest $r): array => $this->contractParameterGroups(),
            'getGroupParameters'         => fn (IRequest $r): array => $this->contractParameters(),
        ];
    }

    /**
     * Shared DynamicTable helper. Mirrors TableService::getTableDataForEntity
     * (lib/Service/TableService.php): returns exactly
     * { allColumnsOrdering, data, settings }.
     *
     * allColumnsOrdering is a sequential LIST of column descriptors
     * ({ key, hidden, title, rules, info }) as produced by sortFields();
     * the rows live under `data` (NOT `rows`), each keyed by field name.
     *
     * @param array<int, array{0: string, 1: string}> $columns list of [key, title]
     * @param array<int, array<string, mixed>>        $rows
     */
    private function table(array $columns, array $rows): array
    {
        $ordering = [];

        foreach ($columns as $column) {
            [$key, $title] = $column;
            $ordering[] = [
                'key'    => $key,
                'hidden' => false,
                'title'  => $this->tr($title),
                'rules'  => null,
                'info'   => null,
            ];
        }

        return [
            'allColumnsOrdering' => $ordering,
            'data'               => $rows,
            'settings'           => [
                'tableColumnView'        => [],
                'tableSortColumns'       => [],
                'tableSortWithinColumns' => [],
                'tableFilter'            => [],
            ],
        ];
    }

    /**
     * Mirror getPaymentsTableData over PaymentsModel.
     */
    private function paymentsTable(): array
    {
        return $this->table(
            [
                ['date', 'Payment date'],
                ['amount', 'Payment amount'],
                ['type_id', 'Payment type'],
                ['payer', 'Payer'],
                ['payee', 'Payee'],
                ['description', 'Description of payment'],
            ],
            [
                [
                    'id'          => 'demo-pay-1',
                    'slug'        => 'demo-pay-1',
                    'slug_type'   => 1,
                    'date'        => '2026-07-05',
                    'amount'      => 'Debit 1200.00',
                    'type_id'     => 'Debit',
                    'payer'       => self::CUSTOMER,
                    'payee'       => 'Done Software Inc',
                    'description' => 'Cloud hosting invoice July',
                ],
                [
                    'id'          => 'demo-pay-2',
                    'slug'        => 'demo-pay-2',
                    'slug_type'   => 1,
                    'date'        => '2026-07-18',
                    'amount'      => 'Credit 3500.00',
                    'type_id'     => 'Credit',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Emma Reid, Product Manager',
                    'description' => 'Design contractor payout',
                ],
                [
                    'id'          => 'demo-pay-3',
                    'slug'        => 'demo-pay-3',
                    'slug_type'   => 1,
                    'date'        => '2026-01-10',
                    'amount'      => 'Debit 2400.00',
                    'type_id'     => 'Debit',
                    'payer'       => 'Contoso GmbH',
                    'payee'       => 'Done Software Inc',
                    'description' => 'Annual license renewal',
                ],
                [
                    'id'          => 'demo-pay-4',
                    'slug'        => 'demo-pay-4',
                    'slug_type'   => 1,
                    'date'        => '2026-02-02',
                    'amount'      => 'Credit 4000.00',
                    'type_id'     => 'Credit',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'John Carter, Backend Developer',
                    'description' => 'January salary',
                ],
                [
                    'id'          => 'demo-pay-5',
                    'slug'        => 'demo-pay-5',
                    'slug_type'   => 1,
                    'date'        => '2026-02-15',
                    'amount'      => 'Debit 850.50',
                    'type_id'     => 'Debit',
                    'payer'       => 'Fabrikam Inc',
                    'payee'       => 'Done Software Inc',
                    'description' => 'Support retainer February',
                ],
                [
                    'id'          => 'demo-pay-6',
                    'slug'        => 'demo-pay-6',
                    'slug_type'   => 1,
                    'date'        => '2026-03-01',
                    'amount'      => 'Credit 1500.00',
                    'type_id'     => 'Credit',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Sophie Lang, Designer',
                    'description' => 'UX audit engagement',
                ],
                [
                    'id'          => 'demo-pay-7',
                    'slug'        => 'demo-pay-7',
                    'slug_type'   => 1,
                    'date'        => '2026-03-22',
                    'amount'      => 'Refund 320.00',
                    'type_id'     => 'Refund',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Adventure Works',
                    'description' => 'Overcharge refund',
                ],
                [
                    'id'          => 'demo-pay-8',
                    'slug'        => 'demo-pay-8',
                    'slug_type'   => 1,
                    'date'        => '2026-04-08',
                    'amount'      => 'Debit 6200.00',
                    'type_id'     => 'Debit',
                    'payer'       => self::CUSTOMER,
                    'payee'       => 'Done Software Inc',
                    'description' => 'Q2 platform subscription',
                ],
                [
                    'id'          => 'demo-pay-9',
                    'slug'        => 'demo-pay-9',
                    'slug_type'   => 1,
                    'date'        => '2026-04-30',
                    'amount'      => 'Fee 45.00',
                    'type_id'     => 'Fee',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Bank of Commerce',
                    'description' => 'Wire transfer fee',
                ],
                [
                    'id'          => 'demo-pay-10',
                    'slug'        => 'demo-pay-10',
                    'slug_type'   => 1,
                    'date'        => '2026-05-12',
                    'amount'      => 'Credit 2750.00',
                    'type_id'     => 'Credit',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Michael Chen, QA Engineer',
                    'description' => 'April contract settlement',
                ],
                [
                    'id'          => 'demo-pay-11',
                    'slug'        => 'demo-pay-11',
                    'slug_type'   => 1,
                    'date'        => '2026-05-27',
                    'amount'      => 'Debit 990.00',
                    'type_id'     => 'Debit',
                    'payer'       => 'Tailspin Toys',
                    'payee'       => 'Done Software Inc',
                    'description' => 'Onboarding setup fee',
                ],
                [
                    'id'          => 'demo-pay-12',
                    'slug'        => 'demo-pay-12',
                    'slug_type'   => 1,
                    'date'        => '2026-06-09',
                    'amount'      => 'Credit 5100.00',
                    'type_id'     => 'Credit',
                    'payer'       => 'Done Software Inc',
                    'payee'       => 'Olivia Brooks, DevOps Engineer',
                    'description' => 'Infrastructure migration',
                ],
                [
                    'id'          => 'demo-pay-13',
                    'slug'        => 'demo-pay-13',
                    'slug_type'   => 1,
                    'date'        => '2026-06-21',
                    'amount'      => 'Debit 1780.25',
                    'type_id'     => 'Debit',
                    'payer'       => 'Contoso GmbH',
                    'payee'       => 'Done Software Inc',
                    'description' => 'Add-on module purchase',
                ],
                [
                    'id'          => 'demo-pay-14',
                    'slug'        => 'demo-pay-14',
                    'slug_type'   => 1,
                    'date'        => '2026-07-28',
                    'amount'      => 'Refund 150.00',
                    'type_id'     => 'Refund',
                    'payer'       => 'Done Software Inc',
                    'payee'       => self::CUSTOMER,
                    'description' => 'Prorated credit',
                ],
            ]
        );
    }

    /**
     * Mirror getContractsTableData over ContractsModel.
     */
    private function contractsTable(): array
    {
        return $this->table(
            [
                ['employee_id', 'Employee'],
                ['start_date', 'Contract start date'],
                ['end_date', 'Contract expiration date'],
                ['is_hourly', 'Is the contract hourly?'],
                ['period_rate', 'Period rate'],
            ],
            [
                [
                    'id'          => self::CONTRACT_ID,
                    'slug'        => self::CONTRACT_ID,
                    'slug_type'   => 1,
                    'employee_id' => 'John Carter, Backend Developer',
                    'start_date'  => '2026-01-01',
                    'end_date'    => '2026-12-31',
                    'is_hourly'   => 'No',
                    'period_rate' => '4000',
                ],
                [
                    'id'          => 'demo-con-2',
                    'slug'        => 'demo-con-2',
                    'slug_type'   => 1,
                    'employee_id' => 'Emma Reid, Product Manager',
                    'start_date'  => '2026-03-01',
                    'end_date'    => '2027-02-28',
                    'is_hourly'   => 'Yes',
                    'period_rate' => '0',
                ],
                [
                    'id'          => 'demo-con-3',
                    'slug'        => 'demo-con-3',
                    'slug_type'   => 1,
                    'employee_id' => 'Sophie Lang, Designer',
                    'start_date'  => '2026-02-15',
                    'end_date'    => '2026-08-15',
                    'is_hourly'   => 'No',
                    'period_rate' => '3200',
                ],
                [
                    'id'          => 'demo-con-4',
                    'slug'        => 'demo-con-4',
                    'slug_type'   => 1,
                    'employee_id' => 'Michael Chen, QA Engineer',
                    'start_date'  => '2026-01-15',
                    'end_date'    => '2026-12-31',
                    'is_hourly'   => 'Yes',
                    'period_rate' => '0',
                ],
                [
                    'id'          => 'demo-con-5',
                    'slug'        => 'demo-con-5',
                    'slug_type'   => 1,
                    'employee_id' => 'Olivia Brooks, DevOps Engineer',
                    'start_date'  => '2026-04-01',
                    'end_date'    => '2027-03-31',
                    'is_hourly'   => 'No',
                    'period_rate' => '5200',
                ],
                [
                    'id'          => 'demo-con-6',
                    'slug'        => 'demo-con-6',
                    'slug_type'   => 1,
                    'employee_id' => 'David Nguyen, Frontend Developer',
                    'start_date'  => '2026-05-01',
                    'end_date'    => '2026-11-30',
                    'is_hourly'   => 'Yes',
                    'period_rate' => '0',
                ],
                [
                    'id'          => 'demo-con-7',
                    'slug'        => 'demo-con-7',
                    'slug_type'   => 1,
                    'employee_id' => 'Laura Bianchi, Accountant',
                    'start_date'  => '2026-06-01',
                    'end_date'    => '2027-05-31',
                    'is_hourly'   => 'No',
                    'period_rate' => '3800',
                ],
            ]
        );
    }

    /**
     * Mirror getContractsParametersTableData over ContractParametersModel.
     */
    private function contractParametersTable(): array
    {
        return $this->table(
            [
                ['name', 'Contract parameter title'],
                ['type_id', 'Contract parameter type ID'],
            ],
            [
                [
                    'id'        => 'demo-par-1',
                    'slug'      => 'demo-par-1',
                    'slug_type' => 1,
                    'name'      => 'Base salary',
                    'type_id'   => 'Number',
                ],
                [
                    'id'        => 'demo-par-2',
                    'slug'      => 'demo-par-2',
                    'slug_type' => 1,
                    'name'      => 'Bonus rate',
                    'type_id'   => 'Percent',
                ],
                [
                    'id'        => 'demo-par-3',
                    'slug'      => 'demo-par-3',
                    'slug_type' => 1,
                    'name'      => 'Total payout',
                    'type_id'   => 'Formula',
                ],
                [
                    'id'        => 'demo-par-4',
                    'slug'      => 'demo-par-4',
                    'slug_type' => 1,
                    'name'      => 'Hourly rate',
                    'type_id'   => 'Number',
                ],
                [
                    'id'        => 'demo-par-5',
                    'slug'      => 'demo-par-5',
                    'slug_type' => 1,
                    'name'      => 'Overtime multiplier',
                    'type_id'   => 'Percent',
                ],
                [
                    'id'        => 'demo-par-6',
                    'slug'      => 'demo-par-6',
                    'slug_type' => 1,
                    'name'      => 'Annual bonus',
                    'type_id'   => 'Formula',
                ],
                [
                    'id'        => 'demo-par-7',
                    'slug'      => 'demo-par-7',
                    'slug_type' => 1,
                    'name'      => 'Tax deduction rate',
                    'type_id'   => 'Percent',
                ],
                [
                    'id'        => 'demo-par-8',
                    'slug'      => 'demo-par-8',
                    'slug_type' => 1,
                    'name'      => 'Net salary',
                    'type_id'   => 'Formula',
                ],
            ]
        );
    }

    /**
     * Mirror getContractParameterGroupsTableData over ContractParameterGroupsModel.
     */
    private function contractParameterGroupsTable(): array
    {
        return $this->table(
            [
                ['name', 'Contract parameter group title'],
                ['parameters_list', 'Parameters'],
            ],
            [
                [
                    'id'              => 'demo-grp-1',
                    'slug'            => 'demo-grp-1',
                    'slug_type'       => 1,
                    'name'            => 'Compensation',
                    'parameters_list' => 'Base salary, Bonus rate, Total payout',
                ],
                [
                    'id'              => 'demo-grp-2',
                    'slug'            => 'demo-grp-2',
                    'slug_type'       => 1,
                    'name'            => 'Hourly terms',
                    'parameters_list' => 'Hourly rate, Overtime multiplier',
                ],
                [
                    'id'              => 'demo-grp-3',
                    'slug'            => 'demo-grp-3',
                    'slug_type'       => 1,
                    'name'            => 'Taxes',
                    'parameters_list' => 'Tax deduction rate, Net salary',
                ],
                [
                    'id'              => 'demo-grp-4',
                    'slug'            => 'demo-grp-4',
                    'slug_type'       => 1,
                    'name'            => 'Annual',
                    'parameters_list' => 'Annual bonus, Total payout',
                ],
            ]
        );
    }

    /**
     * Mirror getPayments (PaymentsModel::getListByFilter): flat list of rows.
     */
    private function payments(): array
    {
        return [
            $this->paymentCard(),
            [
                'id'          => 'demo-pay-2',
                'slug'        => 'demo-pay-2',
                'slug_type'   => 1,
                'date'        => '2026-07-18',
                'amount'      => 3500,
                'type_id'     => '2',
                'payee'       => 'Emma Reid, Product Manager',
                'payer'       => 'Done Software Inc',
                'INN'         => 7712345678,
                'employee_id' => 'demo-user-2',
                'customer_id' => 'demo-cust-1',
                'description' => 'Design contractor payout',
                'comment'     => 'One-time payout',
            ],
            [
                'id'          => 'demo-pay-3',
                'slug'        => 'demo-pay-3',
                'slug_type'   => 1,
                'date'        => '2026-01-10',
                'amount'      => 2400,
                'type_id'     => '1',
                'payee'       => 'Done Software Inc',
                'payer'       => 'Contoso GmbH',
                'INN'         => 7723456789,
                'employee_id' => 'demo-user-1',
                'customer_id' => 'demo-cust-2',
                'description' => 'Annual license renewal',
                'comment'     => 'Paid via bank transfer',
            ],
            [
                'id'          => 'demo-pay-4',
                'slug'        => 'demo-pay-4',
                'slug_type'   => 1,
                'date'        => '2026-02-02',
                'amount'      => 4000,
                'type_id'     => '2',
                'payee'       => 'John Carter, Backend Developer',
                'payer'       => 'Done Software Inc',
                'INN'         => 7798765432,
                'employee_id' => 'demo-user-1',
                'customer_id' => 'demo-cust-1',
                'description' => 'January salary',
                'comment'     => 'Monthly payroll',
            ],
            [
                'id'          => 'demo-pay-5',
                'slug'        => 'demo-pay-5',
                'slug_type'   => 1,
                'date'        => '2026-02-15',
                'amount'      => 850.50,
                'type_id'     => '1',
                'payee'       => 'Done Software Inc',
                'payer'       => 'Fabrikam Inc',
                'INN'         => 7734567890,
                'employee_id' => 'demo-user-4',
                'customer_id' => 'demo-cust-3',
                'description' => 'Support retainer February',
                'comment'     => 'Retainer agreement',
            ],
            [
                'id'          => 'demo-pay-6',
                'slug'        => 'demo-pay-6',
                'slug_type'   => 1,
                'date'        => '2026-03-01',
                'amount'      => 1500,
                'type_id'     => '2',
                'payee'       => 'Sophie Lang, Designer',
                'payer'       => 'Done Software Inc',
                'INN'         => 7745678901,
                'employee_id' => 'demo-user-3',
                'customer_id' => 'demo-cust-1',
                'description' => 'UX audit engagement',
                'comment'     => 'Fixed-price project',
            ],
            [
                'id'          => 'demo-pay-7',
                'slug'        => 'demo-pay-7',
                'slug_type'   => 1,
                'date'        => '2026-03-22',
                'amount'      => 320,
                'type_id'     => '3',
                'payee'       => 'Adventure Works',
                'payer'       => 'Done Software Inc',
                'INN'         => 7756789012,
                'employee_id' => 'demo-user-2',
                'customer_id' => 'demo-cust-4',
                'description' => 'Overcharge refund',
                'comment'     => 'Refund issued',
            ],
            [
                'id'          => 'demo-pay-8',
                'slug'        => 'demo-pay-8',
                'slug_type'   => 1,
                'date'        => '2026-04-08',
                'amount'      => 6200,
                'type_id'     => '1',
                'payee'       => 'Done Software Inc',
                'payer'       => self::CUSTOMER,
                'INN'         => 7798765432,
                'employee_id' => 'demo-user-1',
                'customer_id' => 'demo-cust-1',
                'description' => 'Q2 platform subscription',
                'comment'     => 'Quarterly billing',
            ],
            [
                'id'          => 'demo-pay-9',
                'slug'        => 'demo-pay-9',
                'slug_type'   => 1,
                'date'        => '2026-04-30',
                'amount'      => 45,
                'type_id'     => '4',
                'payee'       => 'Bank of Commerce',
                'payer'       => 'Done Software Inc',
                'INN'         => 7767890123,
                'employee_id' => 'demo-user-7',
                'customer_id' => 'demo-cust-1',
                'description' => 'Wire transfer fee',
                'comment'     => 'Bank charge',
            ],
            [
                'id'          => 'demo-pay-10',
                'slug'        => 'demo-pay-10',
                'slug_type'   => 1,
                'date'        => '2026-05-12',
                'amount'      => 2750,
                'type_id'     => '2',
                'payee'       => 'Michael Chen, QA Engineer',
                'payer'       => 'Done Software Inc',
                'INN'         => 7778901234,
                'employee_id' => 'demo-user-4',
                'customer_id' => 'demo-cust-1',
                'description' => 'April contract settlement',
                'comment'     => 'Contract settlement',
            ],
            [
                'id'          => 'demo-pay-11',
                'slug'        => 'demo-pay-11',
                'slug_type'   => 1,
                'date'        => '2026-05-27',
                'amount'      => 990,
                'type_id'     => '1',
                'payee'       => 'Done Software Inc',
                'payer'       => 'Tailspin Toys',
                'INN'         => 7789012345,
                'employee_id' => 'demo-user-5',
                'customer_id' => 'demo-cust-5',
                'description' => 'Onboarding setup fee',
                'comment'     => 'One-time setup',
            ],
            [
                'id'          => 'demo-pay-12',
                'slug'        => 'demo-pay-12',
                'slug_type'   => 1,
                'date'        => '2026-06-09',
                'amount'      => 5100,
                'type_id'     => '2',
                'payee'       => 'Olivia Brooks, DevOps Engineer',
                'payer'       => 'Done Software Inc',
                'INN'         => 7790123456,
                'employee_id' => 'demo-user-5',
                'customer_id' => 'demo-cust-1',
                'description' => 'Infrastructure migration',
                'comment'     => 'Project milestone',
            ],
            [
                'id'          => 'demo-pay-13',
                'slug'        => 'demo-pay-13',
                'slug_type'   => 1,
                'date'        => '2026-06-21',
                'amount'      => 1780.25,
                'type_id'     => '1',
                'payee'       => 'Done Software Inc',
                'payer'       => 'Contoso GmbH',
                'INN'         => 7723456789,
                'employee_id' => 'demo-user-6',
                'customer_id' => 'demo-cust-2',
                'description' => 'Add-on module purchase',
                'comment'     => 'Add-on purchase',
            ],
            [
                'id'          => 'demo-pay-14',
                'slug'        => 'demo-pay-14',
                'slug_type'   => 1,
                'date'        => '2026-07-28',
                'amount'      => 150,
                'type_id'     => '3',
                'payee'       => self::CUSTOMER,
                'payer'       => 'Done Software Inc',
                'INN'         => 7798765432,
                'employee_id' => 'demo-user-2',
                'customer_id' => 'demo-cust-1',
                'description' => 'Prorated credit',
                'comment'     => 'Prorated refund',
            ],
        ];
    }

    /**
     * Mirror getPayment (PaymentsModel::getItem): a single payment row.
     */
    private function paymentCard(): array
    {
        return [
            'id'          => 'demo-pay-1',
            'slug'        => 'demo-pay-1',
            'slug_type'   => 1,
            'date'        => '2026-07-05',
            'amount'      => 1200,
            'type_id'     => '1',
            'payee'       => 'Done Software Inc',
            'payer'       => self::CUSTOMER,
            'INN'         => 7798765432,
            'employee_id' => 'demo-user-1',
            'customer_id' => 'demo-cust-1',
            'description' => 'Cloud hosting invoice July',
            'comment'     => 'Recurring subscription',
        ];
    }

    /**
     * Mirror getPaymentTypes (PaymentTypesModel::getListByFilter).
     */
    private function paymentTypes(): array
    {
        return [
            ['id' => '1', 'name' => 'Debit'],
            ['id' => '2', 'name' => 'Credit'],
            ['id' => '3', 'name' => 'Refund'],
            ['id' => '4', 'name' => 'Fee'],
        ];
    }

    /**
     * Mirror getSimpleUsers (UserModel::getSimpleUsers): id/slug/slug_type/name.
     */
    private function simpleUsers(): array
    {
        return [
            ['id' => 'demo-user-1', 'slug' => 'demo-user-1', 'slug_type' => 1, 'name' => 'John Carter'],
            ['id' => 'demo-user-2', 'slug' => 'demo-user-2', 'slug_type' => 1, 'name' => 'Emma Reid'],
            ['id' => 'demo-user-3', 'slug' => 'demo-user-3', 'slug_type' => 1, 'name' => 'Sophie Lang'],
            ['id' => 'demo-user-4', 'slug' => 'demo-user-4', 'slug_type' => 1, 'name' => 'Michael Chen'],
            ['id' => 'demo-user-5', 'slug' => 'demo-user-5', 'slug_type' => 1, 'name' => 'Olivia Brooks'],
            ['id' => 'demo-user-6', 'slug' => 'demo-user-6', 'slug_type' => 1, 'name' => 'David Nguyen'],
            ['id' => 'demo-user-7', 'slug' => 'demo-user-7', 'slug_type' => 1, 'name' => 'Laura Bianchi'],
        ];
    }

    /**
     * Mirror getCustomers (CustomersModel::getListByFilter).
     */
    private function customers(): array
    {
        return [
            ['id' => 'demo-cust-1', 'name' => self::CUSTOMER],
            ['id' => 'demo-cust-2', 'name' => 'Contoso GmbH'],
            ['id' => 'demo-cust-3', 'name' => 'Fabrikam Inc'],
            ['id' => 'demo-cust-4', 'name' => 'Adventure Works'],
            ['id' => 'demo-cust-5', 'name' => 'Tailspin Toys'],
        ];
    }

    /**
     * Mirror getContracts (ContractsModel::getListByFilter): flat list of rows.
     */
    private function contracts(): array
    {
        return [
            $this->contractRow(),
            [
                'id'              => 'demo-con-2',
                'slug'            => 'demo-con-2',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-2',
                'start_date'      => '2026-03-01',
                'end_date'        => '2027-02-28',
                'is_hourly'       => true,
                'number_of_hours' => 160,
                'hourly_rate'     => 45,
                'period_rate'     => 0,
                'project_rate'    => 0,
                'created_at'      => '2026-03-01 09:00:00',
                'updated_at'      => '2026-03-01 09:00:00',
            ],
            [
                'id'              => 'demo-con-3',
                'slug'            => 'demo-con-3',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-3',
                'start_date'      => '2026-02-15',
                'end_date'        => '2026-08-15',
                'is_hourly'       => false,
                'number_of_hours' => 0,
                'hourly_rate'     => 0,
                'period_rate'     => 3200,
                'project_rate'    => 0,
                'created_at'      => '2026-02-15 09:00:00',
                'updated_at'      => '2026-02-15 09:00:00',
            ],
            [
                'id'              => 'demo-con-4',
                'slug'            => 'demo-con-4',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-4',
                'start_date'      => '2026-01-15',
                'end_date'        => '2026-12-31',
                'is_hourly'       => true,
                'number_of_hours' => 150,
                'hourly_rate'     => 38,
                'period_rate'     => 0,
                'project_rate'    => 0,
                'created_at'      => '2026-01-15 09:00:00',
                'updated_at'      => '2026-01-15 09:00:00',
            ],
            [
                'id'              => 'demo-con-5',
                'slug'            => 'demo-con-5',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-5',
                'start_date'      => '2026-04-01',
                'end_date'        => '2027-03-31',
                'is_hourly'       => false,
                'number_of_hours' => 0,
                'hourly_rate'     => 0,
                'period_rate'     => 5200,
                'project_rate'    => 0,
                'created_at'      => '2026-04-01 09:00:00',
                'updated_at'      => '2026-04-01 09:00:00',
            ],
            [
                'id'              => 'demo-con-6',
                'slug'            => 'demo-con-6',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-6',
                'start_date'      => '2026-05-01',
                'end_date'        => '2026-11-30',
                'is_hourly'       => true,
                'number_of_hours' => 120,
                'hourly_rate'     => 42,
                'period_rate'     => 0,
                'project_rate'    => 0,
                'created_at'      => '2026-05-01 09:00:00',
                'updated_at'      => '2026-05-01 09:00:00',
            ],
            [
                'id'              => 'demo-con-7',
                'slug'            => 'demo-con-7',
                'slug_type'       => 1,
                'employee_id'     => 'demo-user-7',
                'start_date'      => '2026-06-01',
                'end_date'        => '2027-05-31',
                'is_hourly'       => false,
                'number_of_hours' => 0,
                'hourly_rate'     => 0,
                'period_rate'     => 3800,
                'project_rate'    => 0,
                'created_at'      => '2026-06-01 09:00:00',
                'updated_at'      => '2026-06-01 09:00:00',
            ],
        ];
    }

    /**
     * A single contract model row shared by getContracts / getContractData.
     */
    private function contractRow(): array
    {
        return [
            'id'              => self::CONTRACT_ID,
            'slug'            => self::CONTRACT_ID,
            'slug_type'       => 1,
            'employee_id'     => 'demo-user-1',
            'start_date'      => '2026-01-01',
            'end_date'        => '2026-12-31',
            'is_hourly'       => false,
            'number_of_hours' => 0,
            'hourly_rate'     => 0,
            'period_rate'     => 4000,
            'project_rate'    => 0,
            'created_at'      => '2026-01-01 09:00:00',
            'updated_at'      => '2026-01-01 09:00:00',
        ];
    }

    /**
     * Mirror getContractData (ContractsModel::getContractDataWithCalculations):
     * { model_data, calculated_data, formula_data } keyed by formula parameter id.
     */
    private function contractData(): array
    {
        return [
            'model_data'      => $this->contractRow(),
            'calculated_data' => [
                'demo-par-3' => 4400,
                'demo-par-8' => 3828,
            ],
            'formula_data' => [
                'demo-par-3' => 'Base salary + Bonus rate',
                'demo-par-8' => 'Total payout - Tax deduction rate',
            ],
        ];
    }

    /**
     * Mirror getContractParameter (ContractParametersModel::getItem).
     */
    private function contractParameterCard(): array
    {
        return [
            'id'         => 'demo-par-1',
            'slug'       => 'demo-par-1',
            'slug_type'  => 1,
            'name'       => 'Base salary',
            'type_id'    => 1,
            'created_at' => '2026-01-01 09:00:00',
            'updated_at' => '2026-01-01 09:00:00',
        ];
    }

    /**
     * Mirror getContractParameters / getGroupParameters
     * (ContractParametersModel::getListByFilter).
     */
    private function contractParameters(): array
    {
        return [
            ['id' => 'demo-par-1', 'slug' => 'demo-par-1', 'slug_type' => 1, 'name' => 'Base salary', 'type_id' => 1],
            ['id' => 'demo-par-2', 'slug' => 'demo-par-2', 'slug_type' => 1, 'name' => 'Bonus rate', 'type_id' => 2],
            ['id' => 'demo-par-3', 'slug' => 'demo-par-3', 'slug_type' => 1, 'name' => 'Total payout', 'type_id' => 3],
            ['id' => 'demo-par-4', 'slug' => 'demo-par-4', 'slug_type' => 1, 'name' => 'Hourly rate', 'type_id' => 1],
            ['id' => 'demo-par-5', 'slug' => 'demo-par-5', 'slug_type' => 1, 'name' => 'Overtime multiplier', 'type_id' => 2],
            ['id' => 'demo-par-6', 'slug' => 'demo-par-6', 'slug_type' => 1, 'name' => 'Annual bonus', 'type_id' => 3],
            ['id' => 'demo-par-7', 'slug' => 'demo-par-7', 'slug_type' => 1, 'name' => 'Tax deduction rate', 'type_id' => 2],
            ['id' => 'demo-par-8', 'slug' => 'demo-par-8', 'slug_type' => 1, 'name' => 'Net salary', 'type_id' => 3],
        ];
    }

    /**
     * Mirror getContractParameterValues
     * (ContractParameterValuesModel::getListByFilter enriched with
     * contract_parameter_name + contract_parameter_type_id).
     */
    private function contractParameterValues(): array
    {
        return [
            [
                'id'                         => 'demo-val-1',
                'slug'                       => 'demo-val-1',
                'slug_type'                  => 1,
                'contract_id'                => self::CONTRACT_ID,
                'contract_parameter_id'      => 'demo-par-1',
                'value'                      => '4000',
                'calculated_value'           => '4000',
                'last_calculated_at'         => '2026-07-01 09:00:00',
                'contract_parameter_name'    => 'Base salary',
                'contract_parameter_type_id' => 1,
            ],
            [
                'id'                         => 'demo-val-2',
                'slug'                       => 'demo-val-2',
                'slug_type'                  => 1,
                'contract_id'                => self::CONTRACT_ID,
                'contract_parameter_id'      => 'demo-par-2',
                'value'                      => '10',
                'calculated_value'           => '10',
                'last_calculated_at'         => '2026-07-01 09:00:00',
                'contract_parameter_name'    => 'Bonus rate',
                'contract_parameter_type_id' => 2,
            ],
            [
                'id'                         => 'demo-val-3',
                'slug'                       => 'demo-val-3',
                'slug_type'                  => 1,
                'contract_id'                => self::CONTRACT_ID,
                'contract_parameter_id'      => 'demo-par-3',
                'value'                      => 'Base salary + Bonus rate',
                'calculated_value'           => '4400',
                'last_calculated_at'         => '2026-07-01 09:00:00',
                'contract_parameter_name'    => 'Total payout',
                'contract_parameter_type_id' => 3,
            ],
            [
                'id'                         => 'demo-val-4',
                'slug'                       => 'demo-val-4',
                'slug_type'                  => 1,
                'contract_id'                => self::CONTRACT_ID,
                'contract_parameter_id'      => 'demo-par-7',
                'value'                      => '13',
                'calculated_value'           => '13',
                'last_calculated_at'         => '2026-07-01 09:00:00',
                'contract_parameter_name'    => 'Tax deduction rate',
                'contract_parameter_type_id' => 2,
            ],
            [
                'id'                         => 'demo-val-5',
                'slug'                       => 'demo-val-5',
                'slug_type'                  => 1,
                'contract_id'                => self::CONTRACT_ID,
                'contract_parameter_id'      => 'demo-par-8',
                'value'                      => 'Total payout - Tax deduction rate',
                'calculated_value'           => '3828',
                'last_calculated_at'         => '2026-07-01 09:00:00',
                'contract_parameter_name'    => 'Net salary',
                'contract_parameter_type_id' => 3,
            ],
        ];
    }

    /**
     * Mirror getContractParameterGroup (ContractParameterGroupsModel::getItem).
     */
    private function contractParameterGroupCard(): array
    {
        return [
            'id'              => 'demo-grp-1',
            'slug'            => 'demo-grp-1',
            'slug_type'       => 1,
            'name'            => 'Compensation',
            'parameters_list' => 'Base salary, Bonus rate, Total payout',
            'created_at'      => '2026-01-01 09:00:00',
            'updated_at'      => '2026-01-01 09:00:00',
        ];
    }

    /**
     * Mirror getContractParameterGroups
     * (ContractParameterGroupsModel::getListByFilter).
     */
    private function contractParameterGroups(): array
    {
        return [
            [
                'id'              => 'demo-grp-1',
                'slug'            => 'demo-grp-1',
                'slug_type'       => 1,
                'name'            => 'Compensation',
                'parameters_list' => 'Base salary, Bonus rate, Total payout',
            ],
            [
                'id'              => 'demo-grp-2',
                'slug'            => 'demo-grp-2',
                'slug_type'       => 1,
                'name'            => 'Hourly terms',
                'parameters_list' => 'Hourly rate, Overtime multiplier',
            ],
            [
                'id'              => 'demo-grp-3',
                'slug'            => 'demo-grp-3',
                'slug_type'       => 1,
                'name'            => 'Taxes',
                'parameters_list' => 'Tax deduction rate, Net salary',
            ],
            [
                'id'              => 'demo-grp-4',
                'slug'            => 'demo-grp-4',
                'slug_type'       => 1,
                'name'            => 'Annual',
                'parameters_list' => 'Annual bonus, Total payout',
            ],
        ];
    }
}

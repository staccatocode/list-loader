<?php

/*
 * This file is part of staccato listable component
 *
 * (c) Krystian Karaś <dev@karashome.pl>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Staccato\Component\Listable\Tests\Repository;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Staccato\Component\Listable\ListStateInterface;
use Staccato\Component\Listable\Repository\AbstractRepository;
use Staccato\Component\Listable\Repository\ArrayRepository;

/**
 * @covers \Staccato\Component\Listable\Repository\ArrayRepository
 */
class ArrayRepositoryTest extends TestCase
{
    /**
     * @var MockObject|ListStateInterface|null
     */
    private $state;

    public function setUp(): void
    {
        $this->state = $this->getMockBuilder(ListStateInterface::class)->getMock();
    }

    public function testCreate(): void
    {
        $repository = new ArrayRepository();
        $repository->setOptions([
            'data' => [],
        ]);

        $this->assertInstanceOf(AbstractRepository::class, $repository);
    }

    public function testGetResult(): void
    {
        $data = $this->prepareTestData(100);

        $this->state
            ->method('getPage')
            ->willReturn(0)
        ;

        $this->state
            ->method('getLimit')
            ->willReturn(1)
        ;

        $repository = new ArrayRepository();
        $repository->setOptions([
            'data' => $data,
        ]);

        $result = $repository->getResult($this->state);

        $this->assertEquals(100, $result->getTotalCount());
        $this->assertSame([['a' => 'Test 0', 'b' => 1]], $result->getRows());
    }

    /**
     * @dataProvider stateFilterResultDataProvider
     */
    public function testStateFilterResult(array $filters, int $limit, array $expectedRows, int $expectedTotalCount): void
    {
        $data = $this->prepareTestData(100);

        $this->state
            ->method('getPage')
            ->willReturn(0)
        ;

        $this->state
            ->method('getLimit')
            ->willReturn($limit)
        ;

        $this->state
            ->method('getFilters')
            ->willReturn($filters)
        ;

        $repository = new ArrayRepository();
        $repository->setOptions([
            'data' => $data,
        ]);

        $result = $repository->getResult($this->state);

        $this->assertEquals($expectedTotalCount, $result->getTotalCount());
        $this->assertSame($expectedRows, $result->getRows());
    }

    public function testOptionFilter(): void
    {
        $data = $this->prepareTestData(100);

        $this->state
            ->method('getPage')
            ->willReturn(0)
        ;

        $this->state
            ->method('getLimit')
            ->willReturn(1)
        ;

        $this->state
            ->method('getFilters')
            ->willReturn([null])
        ;

        $repository = new ArrayRepository();
        $repository->setOptions([
            'data' => $data,
            'filter' => static function (array &$rows, ListStateInterface $state) {
                return \array_slice($rows, 0, 50);
            },
        ]);

        $result = $repository->getResult($this->state);

        $this->assertEquals(50, $result->getTotalCount());
        $this->assertSame(\array_slice($data, 0, 1), $result->getRows());
    }

    public function testSortResult(): void
    {
        $data = $this->prepareTestData(100);

        $this->state
            ->method('getPage')
            ->willReturn(0)
        ;

        $this->state
            ->method('getLimit')
            ->willReturn(1)
        ;

        $this->state
            ->method('getSorter')
            ->willReturn(['a' => 'Desc'])
        ;

        $repository = new ArrayRepository();
        $repository->setOptions([
            'data' => $data,
        ]);

        $result = $repository->getResult($this->state);

        $this->assertEquals(100, $result->getTotalCount());
        $this->assertSame([['a' => 'Test 99', 'b' => 100]], $result->getRows());

        $repository->setOptions([
            'data' => $data,
            'sort' => static function (array &$rows, ListStateInterface $state) {
                return $rows;
            },
        ]);

        $result = $repository->getResult($this->state);

        $this->assertEquals(100, $result->getTotalCount());
        $this->assertSame([['a' => 'Test 0', 'b' => 1]], $result->getRows());
    }

    public function stateFilterResultDataProvider(): array
    {
        return [
            [
                ['a' => 'test 9', 'b' => '100'],
                1,
                [['a' => 'Test 99', 'b' => 100]],
                1,
            ],
            [
                ['a' => 'test', 'b' => ['from' => '97', 'to' => '99']],
                2,
                [
                    ['a' => 'Test 96', 'b' => 97],
                    ['a' => 'Test 97', 'b' => 98],
                ],
                3,
            ],
        ];
    }

    private function prepareTestData(int $length = 100): array
    {
        $data = [];
        for ($i = 0; $i < $length; ++$i) {
            $data[$i] = [
                'a' => 'Test ' . $i,
                'b' => $i + 1,
            ];
        }

        return $data;
    }
}

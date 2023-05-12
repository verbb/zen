<?php
namespace verbb\zen\models;

use Diff\Differ\Differ;
use Diff\Differ\ListDiffer;
use Diff\Comparer\StrictComparer;
use Diff\Comparer\ValueComparer;
use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use Exception;
use LogicException;

class MapDiffer implements Differ {

    /**
     * @var bool
     */
    private $recursively;

    /**
     * @var Differ
     */
    private $listDiffer;

    /**
     * @var ValueComparer
     */
    private $valueComparer;

    /**
     * The third argument ($comparer) was added in 3.0
     */
    public function __construct( bool $recursively = false, Differ $listDiffer = null, ValueComparer $comparer = null ) {
        $this->recursively = $recursively;
        $this->listDiffer = $listDiffer ?? new ListDiffer();
        $this->valueComparer = $comparer ?? new StrictComparer();
    }

    /**
     * @see Differ::doDiff
     *
     * Computes the diff between two associate arrays.
     *
     * @since 0.4
     *
     * @param array $oldValues The first array
     * @param array $newValues The second array
     *
     * @throws Exception
     * @return DiffOp[]
     */
    public function doDiff( array $oldValues, array $newValues ): array {
        $newSet = $this->arrayDiffAssoc( $newValues, $oldValues );
        $oldSet = $this->arrayDiffAssoc( $oldValues, $newValues );

        $diffSet = [];

        foreach ( $this->getAllKeys( $oldSet, $newSet ) as $key ) {
            $diffOp = $this->getDiffOpForElement( $key, $oldSet, $newSet );

            if ( $diffOp !== null ) {
                $diffSet[$key] = $diffOp;
            }
        }

        return $diffSet;
    }

    private function getAllKeys( array $oldSet, array $newSet ): array {
        return array_unique( array_merge(
            array_keys( $oldSet ),
            array_keys( $newSet )
        ) );
    }

    private function getDiffOpForElement( $key, array $oldSet, array $newSet ) {
        // \Craft::dump($key);

        if ( $this->recursively ) {
            $diffOp = $this->getDiffOpForElementRecursively( $key, $oldSet, $newSet );

            if ( $diffOp !== null ) {
                if ( $diffOp->isEmpty() ) {
                    // there is no (relevant) difference
                    return null;
                } else {
                    return $diffOp;
                }
            }
        }

        $hasOld = array_key_exists( $key, $oldSet );
        $hasNew = array_key_exists( $key, $newSet );

        // if ($key == 'icon') {


        
// }

        if ( $hasOld && $hasNew ) {
            return new DiffOpChange( $oldSet[$key], $newSet[$key] );
        }
        elseif ( $hasOld ) {
            return new DiffOpRemove( $oldSet[$key] );
        }
        elseif ( $hasNew ) {
            return new DiffOpAdd( $newSet[$key] );
        }

        // @codeCoverageIgnoreStart
        throw new LogicException( 'The element needs to exist in either the old or new list to compare' );
        // @codeCoverageIgnoreEnd
    }

    private function getDiffOpForElementRecursively( $key, array $oldSet, array $newSet ) {
        // \Craft::dump($key);

        $old = array_key_exists( $key, $oldSet ) ? $oldSet[$key] : [];
        $new = array_key_exists( $key, $newSet ) ? $newSet[$key] : [];

        if ( is_array( $old ) && is_array( $new ) ) {
            return $this->getDiffForArrays( $old, $new );
        }

        return null;
    }

    private function getDiffForArrays( array $old, array $new ): Diff {
        // CHANGE: Don't use `listDiffer` because it creates gaps in element fields which are
        // numerically-indexed. When the get new values it causes non-sequential data like:
        // 'test' => [
        //     0 => 'some value',
        //     4 => 'another value'
        // ]
        return new Diff( $this->doDiff( $old, $new ), true );
    }

    /**
     * Returns if an array is associative or not.
     *
     * @param array $array
     *
     * @return bool
     */
    private function isAssociative( array $array ): bool {
        foreach ( $array as $key => $value ) {
            if ( is_string( $key ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Similar to the native array_diff_assoc function, except that it will
     * spot differences between array values. Very weird the native
     * function just ignores these...
     *
     * @see http://php.net/manual/en/function.array-diff-assoc.php
     *
     * @param array $from
     * @param array $to
     *
     * @return array
     */
    private function arrayDiffAssoc( array $from, array $to ): array {
        $diff = [];

        foreach ( $from as $key => $value ) {
            if ( !array_key_exists( $key, $to ) || !$this->valueComparer->valuesAreEqual( $to[$key], $value ) ) {
                $diff[$key] = $value;
            }
        }

        return $diff;
    }

}

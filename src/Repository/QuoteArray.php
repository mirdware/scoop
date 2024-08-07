<?php

namespace App\Repository;

class QuoteArray implements Quote
{
    /**
     * @return array<array<string, string>>
     */
    public function publish()
    {
        return array(
            array(
                'quote' => 'When you do things right, people won\'t be sure you\'ve done anything at all.',
                'author' => 'God'
            ),
            array(
                'quote' => 'Walking on water and developing software from a specification are easy if both are frozen.',
                'author' => 'Edward Berard'
            ),
            array(
                'quote' => 'Considering the current sad state of our computer programs,
                software development is clearly still a black art, and cannot yet be called an engineering discipline.',
                'author' => 'Bill Clinton'
            ),
            array(
                'quote' => 'To iterate is human, to recurse divine.',
                'author' => ' L. Peter Deutsch'
            ),
            array(
                'quote' => 'Talk is cheap. Show me the code.',
                'author' => 'Linus Torvalds'
            ),
            array(
                'quote' => 'The trouble with programmers is that you can never
                tell what a programmer is doing until it’s too late.',
                'author' => 'Seymour Cray'
            ),
            array(
                'quote' => 'Measuring programming progress by lines of code
                is like measuring aircraft building progress by weight.',
                'author' => 'Bill Gates'
            ),
            array(
                'quote' => 'Most of you are familiar with the virtues of a programmer.
                There are three, of course: laziness, impatience, and hubris',
                'author' => 'Larry Wall'
            ),
            array(
                'quote' => 'Always code as if the guy who ends up maintaining
                your code will be a violent psychopath who knows where you live.',
                'author' => 'Martin Golding'
            )
        );
    }
}

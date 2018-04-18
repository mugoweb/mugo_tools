<?php
/*  Copyright 2011  Patrick Galbraith  (email : patrick.j.galbraith@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation. 

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
    
    
    Taken from http://www.pjgalbraith.com/2011/11/truncating-text-html-with-php/
    
    Example usage:
    
    $truncate = new TruncateHTML;
    
    // Show first 20 characters only; don't worry about preserving whole words; add ellipses
    $truncated = $truncate->truncateChars( $html, 20, '...', false );
    
    // Show first 20 characters only; preserve whole words; add ellipses
    $truncated = $truncate->truncateChars( $html, 20, '...', true );
    
    // Show first 20 words only; add ellipses
    $truncated = $truncate->truncateWords( $html, 20, '...', false );
    
    Some modifications by peter@mugo.ca
    - Add extra feature to preserve words when doing a character-based cut
    - Change saveHTML to saveXML to preserve self-closing tags
    - Check to prevent an empty ellipse from being added if an entire paragraph completes the character / word requirement
    - Code styling changes around spacing and placement of opening and closing braces
    - Force UTF-8
*/

class TruncateHTML
{
    
    var $charCount = 0;
    var $wordCount = 0;
    var $limit;
    var $startNode;
    var $ellipsis;
    var $foundBreakpoint = false;
    
    private function init( $html, $limit, $ellipsis )
    {
        $dom = new DOMDocument();
        // Force utf8
        $dom->loadHTML( '<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $html );
        
        $this->startNode = $dom->getElementsByTagName("body")->item(0); //the body tag node, our html fragment is automatically wrapped in a <html><body> etc... skeleton which we will strip later
        $this->limit = $limit;
        $this->ellipsis = $ellipsis;
        $this->charCount = 0;
        $this->wordCount = 0;
        $this->foundBreakpoint = false;
        
        return $dom;
    }
    
    public function truncateChars( $html, $limit, $ellipsis = '...', $preserveWords = true )
    {
        if( $limit <= 0 || $limit >= strlen( strip_tags( $html ) ) )
        {
            return $html;
        }
        
        $dom = $this->init( $html, $limit, $ellipsis );
        
        $this->domNodeTruncateChars( $this->startNode, $preserveWords ); //pass the body node on to be processed
        
        //return preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body|meta))[^>]*>\s*~i', '', $dom->saveHTML()); //hack to remove the html skeleton that is added, unfortunately this can't be avoided unless php > 5.3
        // Use saveXML to preserve self-closing tags, and pass in documentElement to remove the XML declaration
        
        $return = preg_replace( '~<(?:!DOCTYPE|/?(?:html|head|body|meta))[^>]*>\s*~i', '', $dom->saveXML( $dom->documentElement ) );
        
        // support html in the ellipsis
        $return = htmlspecialchars_decode( $return );
        
        return $return;
    }
    
    public function truncateWords( $html, $limit, $ellipsis = '...' )
    {
        if($limit <= 0 || $limit >= $this->countWords( strip_tags( $html ) ) )
        {
            return $html;
        }
        
        $dom = $this->init( $html, $limit, $ellipsis );
        
        $this->domNodeTruncateWords( $this->startNode ); //pass the body node on to be processed
        
        //return preg_replace('~<(?:!DOCTYPE|/?(?:html|head|body|meta))[^>]*>\s*~i', '', $dom->saveXML()); //hack to remove the html skeleton that is added, unfortunately this can't be avoided unless php > 5.3
        // Use saveXML to preserve self-closing tags, and pass in documentElement to remove the XML declaration
        
        $return = preg_replace( '~<(?:!DOCTYPE|/?(?:html|head|body|meta))[^>]*>\s*~i', '', $dom->saveXML( $dom->documentElement ) );
        
        // support html in the ellipsis
        $return = htmlspecialchars_decode( $return );
        
        return $return;
    }
    
    private function domNodeTruncateChars( DOMNode $domNode, $preserveWords = true )
    {
        foreach( $domNode->childNodes as $node )
        {
            
            if( $this->foundBreakpoint == true )
            {
                return;
            }
            
            if( $node->hasChildNodes() )
            {
                $this->domNodeTruncateChars( $node, $preserveWords );
            }
            else
            {
                if( ( $this->charCount + strlen( $node->nodeValue ) ) >= $this->limit )
                {
                    //we have found our end point
                    if( $preserveWords )
                    {
                        // Note: each word element is an array: 0 = the word; 1 = the offset
                        $words = preg_split("/[\n\r\t ]+/", $node->nodeValue, 0, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_OFFSET_CAPTURE);
                        
                        $nodeCharacterEndPoint = 0;
                        $charactersRemaining = $this->limit - $this->charCount;
                        foreach( $words as $word )
                        {
                            $charactersRemaining = $charactersRemaining - strlen( $word[0] );
                            // Allow for characters remaining to be at most 1, since we account for the space
                            if( $charactersRemaining <= 1 )
                            {
                                $nodeCharacterEndPoint = $word[1] + strlen( $word[0] );
                                break;
                            }
                            else
                            {
                                // Account for the space
                                $charactersRemaining = $charactersRemaining - 1;
                            }
                        }
                        $node->nodeValue = substr( $node->nodeValue, 0, $nodeCharacterEndPoint );
                    }
                    else
                    {
                        $node->nodeValue = substr( $node->nodeValue, 0, $this->limit - $this->charCount );
                    }
                    
                    $this->removeProceedingNodes( $node );
                    $this->insertEllipsis( $node );
                    $this->foundBreakpoint = true;
                    return;
                }
                else
                {
                    $this->charCount += strlen( $node->nodeValue );
                }
            }
        }
    }
    
    private function domNodeTruncateWords( DOMNode $domNode )
    {
        foreach( $domNode->childNodes as $node )
        {
            if( $this->foundBreakpoint == true )
            {
                return;
            }
            
            if( $node->hasChildNodes() )
            {
                $this->domNodeTruncateWords( $node );
            }
            else
            {
                $curWordCount = $this->countWords($node->nodeValue);
                
                if( ( $this->wordCount + $curWordCount ) >= $this->limit )
                {
                    //we have found our end point
                    if( $curWordCount > 1 && ( $this->limit - $this->wordCount ) < $curWordCount )
                    {
                        $words = preg_split("/[\n\r\t ]+/", $node->nodeValue, ( $this->limit - $this->wordCount ) + 1, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_OFFSET_CAPTURE );
                        end( $words );
                        $last_word = prev( $words );
                        $node->nodeValue = substr( $node->nodeValue, 0, $last_word[1] + strlen( $last_word[0] ) );
                    }
                    
                    $this->removeProceedingNodes( $node );
                    $this->insertEllipsis( $node );
                    $this->foundBreakpoint = true;
                    return;
                }
                else
                {
                    $this->wordCount += $curWordCount;
                }
            }
        }
    }
    
    private function removeProceedingNodes( DOMNode $domNode )
    {
        $nextNode = $domNode->nextSibling;
        
        if( $nextNode !== NULL )
        {
            $this->removeProceedingNodes( $nextNode );
            $domNode->parentNode->removeChild( $nextNode );
        }
        else
        {
            //scan upwards till we find a sibling
            $curNode = $domNode->parentNode;
            while( $curNode !== $this->startNode )
            {
                if( $curNode->nextSibling !== NULL )
                {
                    $curNode = $curNode->nextSibling;
                    $this->removeProceedingNodes( $curNode );
                    $curNode->parentNode->removeChild( $curNode );
                    break;
                }
                $curNode = $curNode->parentNode;
            }
        }
    }
    
    private function insertEllipsis( DOMNode $domNode )
    {
        $avoid = array( 'a', 'strong', 'em', 'h1', 'h2', 'h3', 'h4', 'h5' ); //html tags to avoid appending the ellipsis to
        
        if( in_array( $domNode->parentNode->nodeName, $avoid ) && ( $domNode->parentNode->parentNode !== NULL || $domNode->parentNode->parentNode !== $this->startNode ) )
        {
            // Append as text node to parent instead
            $textNode = new DOMText($this->ellipsis);
            
            if( $domNode->parentNode->parentNode->nextSibling )
            {
                $domNode->parentNode->parentNode->insertBefore( $textNode, $domNode->parentNode->parentNode->nextSibling );
            }
            else
            {
                $domNode->parentNode->parentNode->appendChild( $textNode );
            }
        }
        else
        {
            // Append to current node if the node hasn't been completed (to prevent an empty ellipsis line)
            if( '' != trim( $domNode->nodeValue ) )
            {
                $domNode->nodeValue = rtrim( $domNode->nodeValue ) . $this->ellipsis;
            }
        }
    }
    
    private function countWords( $text )
    {
        $words = preg_split( "/[\n\r\t ]+/", $text, -1, PREG_SPLIT_NO_EMPTY );
        return count( $words );
    }
}
?>

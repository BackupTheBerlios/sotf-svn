
/*  
 * $Id$
 * Created for the StreamOnTheFly project (IST-2001-32226)
 * Authors: András Micsik, Máté Pataki, Tamás Déri 
 *          at MTA SZTAKI DSD, http://dsd.sztaki.hu
 */

advanced_query := sort_order ( |A query_tag ) *

sort_order := sort_field [" DESC"] "|B" sort_field [" DESC"]

sort_field := database columns from sotf_programme + station, seriestitle, etc.

query_tag := ("AND" | "OR") "|B" sql_field "|B" operator "|B" pattern "|B" type

sql_field := database columns from sotf_programme + station, seriestitle, etc.

type := number | date | string | lang | person | station | rating | length

operator :=  is | is_not | contains | is_equal | is_not_equal | does_not_contain | bigger | smaller | begins_with

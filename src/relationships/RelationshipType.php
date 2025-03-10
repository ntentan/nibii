<?php

namespace ntentan\nibii\relationships;

enum RelationshipType
{
    case BELONGS_TO;
    case HAS_MANY;
    case MANY_HAVE_MANY;
}
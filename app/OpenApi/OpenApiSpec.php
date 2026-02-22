<?php

declare(strict_types=1);

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="StudAI Career API",
 *     version="2.0.0",
 *     description="StudAI Career — AI-Powered Career Development & Negotiation Platform API.
 *
 * Provides endpoints for resume analysis, job matching, interview preparation,
 * salary negotiation, autonomous agent operations, SCOUT employer features,
 * market intelligence, and learning path management.
 *
 * Authentication: Bearer token via Laravel Sanctum.",
 *     @OA\Contact(
 *         name="StudAI Career Support",
 *         email="support@studai.career"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://studai.career/terms"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="Local Development Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Enter Sanctum personal access token"
 * )
 *
 * @OA\Tag(name="Auth", description="Authentication & Registration")
 * @OA\Tag(name="Resume", description="Resume Management & AI Optimization")
 * @OA\Tag(name="Jobs", description="Job Discovery & Matching")
 * @OA\Tag(name="Applications", description="Job Application Tracking")
 * @OA\Tag(name="Interviews", description="Interview Preparation & Mock Sessions")
 * @OA\Tag(name="Negotiation", description="Salary Negotiation Strategy")
 * @OA\Tag(name="Agent", description="Autonomous Job-Hunt Agent")
 * @OA\Tag(name="SCOUT", description="SCOUT Employer Predictive Hiring")
 * @OA\Tag(name="Market Intelligence", description="Job Market Analytics")
 * @OA\Tag(name="Skills", description="Skill Assessment & Gap Analysis")
 * @OA\Tag(name="Learning", description="Learning Path & Skill Development")
 * @OA\Tag(name="Payments", description="Subscription & Payment Management")
 * @OA\Tag(name="Notifications", description="User Notification Management")
 * @OA\Tag(name="GDPR", description="Data Privacy & GDPR Compliance")
 * @OA\Tag(name="Webhooks", description="Third-Party Webhook Handlers")
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     @OA\Property(property="message", type="string", example="Unauthenticated."),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="SuccessResponse",
 *     @OA\Property(property="success", type="boolean", example=true),
 *     @OA\Property(property="message", type="string", example="Operation completed"),
 *     @OA\Property(property="data", type="object", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="meta", type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="last_page", type="integer", example=5),
 *         @OA\Property(property="per_page", type="integer", example=15),
 *         @OA\Property(property="total", type="integer", example=75)
 *     ),
 *     @OA\Property(property="links", type="object",
 *         @OA\Property(property="first", type="string"),
 *         @OA\Property(property="last", type="string"),
 *         @OA\Property(property="prev", type="string", nullable=true),
 *         @OA\Property(property="next", type="string", nullable=true)
 *     )
 * )
 */
class OpenApiSpec
{
    // This class serves as the base OpenAPI specification definition.
    // Individual endpoint annotations are in their respective controllers.
}
